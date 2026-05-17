// Names: MAHIKA, DEON, TAUHIR
// Surnames: LALA, STEENKAMP, SEPTEMBER
// Student Numbers: 25013727, 25135742, 24750982

//process.argv - accessing command line arguments

"use strict";

require("dotenv").config();
const WebSocket = require("ws");
const readline  = require("readline");
const fetch     = (...args) => import("node-fetch").then(({ default: f }) => f(...args));

const API_URL        = process.env.API_BASE      || "https://wheatley.cs.up.ac.za/u25013727/api.php";
const SERVER_API_KEY = process.env.SERVER_API_KEY || "flighttracker_server_2026";
const WH_USER        = process.env.WHEATLEY_USER  || "u25013727";
const WH_PASS        = process.env.WHEATLEY_PASS  || "Elephant@130206";

function apiUrl() {
    if (WH_USER && WH_PASS) {
        return API_URL.replace("https://", `https://${WH_USER}:${WH_PASS}@`);
    }
    return API_URL;
}

const clients = new Map();
const activeFlights = new Map();
const boardingTimers = new Map();

async function apiPost(body) {
    try {
        const res  = await fetch(apiUrl(), {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(body),
        });
        return await res.json();
    } catch (err) {
        console.error("[API] Fetch error:", err.message);
        return { status: "error", data: err.message };
    }
}

async function getFlightDetails(flightId) {
    return apiPost({ type: "GetFlight", apikey: SERVER_API_KEY, flight_id: flightId });
}

async function getAllFlights(apikey) {
    return apiPost({ type: "GetAllFlights", apikey });
}

async function dispatchFlight(flightId, apikey) {
    return apiPost({ type: "DispatchFlight", apikey, flight_id: flightId });
}

async function boardFlight(flightId, apikey) {
    return apiPost({ type: "BoardFlight", apikey, flight_id: flightId });
}

async function updateFlightPosition(flightId, lat, lng, status) {
    return apiPost({
        type:       "UpdateFlightPosition",
        server_key: SERVER_API_KEY,
        flight_id:  flightId,
        latitude:   lat,
        longitude:  lng,
        status,
    });
}

async function getAirports() {
    return apiPost({ type: "GetAirports", apikey: SERVER_API_KEY });
}

function send(ws, obj) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify(obj));
    }
}

function sendToUser(username, obj) {
    const c = clients.get(username);
    if (c) send(c.ws, obj);
}

function broadcast(obj, predicate = () => true) {
    for (const [username, c] of clients) {
        if (predicate(username, c)) send(c.ws, obj);
    }
}

function lerp(a, b, t) { return a + (b - a) * t; }

const TICK_MS = 250;
const DB_WRITE_EVERY_N_TICKS = Math.round(5000 / TICK_MS);

function startFlightAnimation(flightId, flightData) {
    if (activeFlights.has(flightId)) return; // already running

    const durationSecs = parseFloat(flightData.flight_duration_hours); // hours → seconds (scaled)
    const totalTicks   = Math.round((durationSecs * 1000) / TICK_MS);

    const state = {
        flightId,
        flightNumber: flightData.flight_number,
        durationSecs,
        originLat:  parseFloat(flightData.origin_lat),
        originLng:  parseFloat(flightData.origin_lng),
        destLat:    parseFloat(flightData.destination_lat),
        destLng:    parseFloat(flightData.destination_lng),
        currentTick: 0,
        totalTicks,
        subscribers: new Set(), // usernames tracking this flight
        dbTickCounter: 0,
        intervalId: null,
    };

    state.intervalId = setInterval(async () => {
        state.currentTick++;
        state.dbTickCounter++;

        const progress = Math.min(state.currentTick / state.totalTicks, 1);
        const lat = lerp(state.originLat, state.destLat, progress);
        const lng = lerp(state.originLng, state.destLng, progress);
        const landed = progress >= 1;

        // Write to DB on interval (every ~5 s) or final tick
        if (state.dbTickCounter >= DB_WRITE_EVERY_N_TICKS || landed) {
            state.dbTickCounter = 0;
            const newStatus = landed ? "Landed" : "In Flight";
            await updateFlightPosition(flightId, lat, lng, newStatus);
        }

        // Broadcast POSITION to all subscribers
        const posMsg = {
            type:          "POSITION",
            flight_id:     flightId,
            flight_number: state.flightNumber,
            latitude:      lat,
            longitude:     lng,
            progress:      Math.round(progress * 100),
            status:        landed ? "Landed" : "In Flight",
        };

        for (const username of state.subscribers) {
            sendToUser(username, posMsg);
        }

        if (landed) {
            clearInterval(state.intervalId);
            activeFlights.delete(flightId);
            console.log(`[FLIGHT] ${state.flightNumber} has landed.`);

            // Notify all subscribers that the flight landed
            for (const username of state.subscribers) {
                sendToUser(username, {
                    type:          "FLIGHT_LANDED",
                    flight_id:     flightId,
                    flight_number: state.flightNumber,
                    message:       `Flight ${state.flightNumber} has landed.`,
                });
            }
        }
    }, TICK_MS);

    activeFlights.set(flightId, state);
    console.log(`[FLIGHT] Animation started: ${flightData.flight_number} (${durationSecs}s)`);
}

async function handleAuth(ws, payload) {
    const { apikey } = payload;
    if (!apikey) {
        return send(ws, { type: "ERROR", code: 400, message: "Missing apikey." });
    }

    // Validate by calling Login endpoint indirectly – use GetAllFlights which validates the key
    const resp = await getAllFlights(apikey);
    if (resp.status === "error") {
        return send(ws, { type: "ERROR", code: 403, message: "Authentication failed: " + resp.data });
    }

    // Pull user info from the response meta or a separate Login call
    // We stored type in the flight list response; fall back to a Login call
    let role = resp.role; // if API returns it
    let username = resp.username;
    let userId   = resp.user_id;

    // If the API does not return those fields, do a dedicated login lookup
    if (!role || !username) {
        const loginResp = await apiPost({ type: "Login", email: payload.email || "", password: payload.password || "" });
        if (loginResp.status === "success" && loginResp.data && loginResp.data[0]) {
            role     = loginResp.data[0].type;
            username = loginResp.data[0].name + "_" + loginResp.data[0].surname;
            userId   = loginResp.data[0].id;
        } else {
            // The client should send auth via Login first, then authenticate the WS
            // Accept the apikey and get the role from the API's GetAllFlights data
            role     = payload.role     || "Passenger";
            username = payload.username || ("user_" + Date.now());
            userId   = payload.user_id  || null;
        }
    }

    // Remove any stale connection for this username
    if (clients.has(username)) {
        const old = clients.get(username);
        try { old.ws.terminate(); } catch (_) {}
    }

    clients.set(username, { ws, role, userId, apikey });
    ws._username = username;

    console.log(`[AUTH] ${username} (${role}) connected.`);

    send(ws, {
        type:     "AUTH_OK",
        username,
        role,
        message:  `Welcome, ${username}! You are connected as ${role}.`,
    });
}

async function handleDispatch(ws, payload) {
    const sender = clients.get(ws._username);
    if (!sender || sender.role !== "ATC") {
        return send(ws, { type: "ERROR", code: 403, message: "Only ATC can dispatch flights." });
    }

    const { flight_id } = payload;
    if (!flight_id) return send(ws, { type: "ERROR", code: 400, message: "Missing flight_id." });

    // Call DispatchFlight on the API
    const dispResp = await dispatchFlight(flight_id, sender.apikey);
    if (dispResp.status === "error") {
        return send(ws, { type: "ERROR", code: 400, message: "Dispatch failed: " + dispResp.data });
    }

    console.log(`[DISPATCH] ATC ${ws._username} dispatched flight ${flight_id}`);

    // Fetch full flight details to get airport coords and passenger list
    const flightResp = await getFlightDetails(flight_id);
    if (flightResp.status === "error" || !flightResp.data || !flightResp.data[0]) {
        return send(ws, { type: "ERROR", code: 500, message: "Could not retrieve flight details after dispatch." });
    }

    const flight = flightResp.data[0];

    // Notify ATC of success
    send(ws, {
        type:          "DISPATCH_OK",
        flight_id,
        flight_number: flight.flight_number,
        message:       `Flight ${flight.flight_number} dispatched. Boarding window open.`,
    });

    // Broadcast BOARDING_CALL to all passengers booked on this flight
    const passengerIds = (flight.passengers || []).map(p => p.passenger_id || p.id);

    // Find connected passengers booked on this flight
    for (const [username, client] of clients) {
        if (client.role === "Passenger") {
            // Check if this passenger is booked by looking at their flights
            const pFlightsResp = await getAllFlights(client.apikey);
            const booked = (pFlightsResp.data || []).some(f => parseInt(f.id) === parseInt(flight_id));
            if (booked) {
                send(client.ws, {
                    type:          "BOARDING_CALL",
                    flight_id,
                    flight_number: flight.flight_number,
                    message:       `Your flight ${flight.flight_number} is now boarding! You have 60 seconds to confirm.`,
                    window_seconds: 60,
                });
            }
        }
    }

    // Start 60-second boarding window, then transition to In Flight
    if (boardingTimers.has(flight_id)) clearTimeout(boardingTimers.get(flight_id));

    const timer = setTimeout(async () => {
        boardingTimers.delete(flight_id);
        console.log(`[BOARDING] 60s window expired for flight ${flight_id}. Starting animation.`);

        // Update status to In Flight via UpdateFlightPosition at origin
        await updateFlightPosition(
            flight_id,
            parseFloat(flight.origin_lat   || flight.current_latitude),
            parseFloat(flight.origin_lng   || flight.current_longitude),
            "In Flight"
        );

        // Notify all connected clients
        broadcast({
            type:          "STATUS_UPDATE",
            flight_id,
            flight_number: flight.flight_number,
            status:        "In Flight",
            message:       `Flight ${flight.flight_number} is now In Flight.`,
        });

        // Start position animation
        startFlightAnimation(flight_id, flight);

    }, 60_000);

    boardingTimers.set(flight_id, timer);
}

async function handleBoard(ws, payload) {
    const sender = clients.get(ws._username);
    if (!sender || sender.role !== "Passenger") {
        return send(ws, { type: "ERROR", code: 403, message: "Only Passengers can confirm boarding." });
    }

    const { flight_id } = payload;
    if (!flight_id) return send(ws, { type: "ERROR", code: 400, message: "Missing flight_id." });

    const resp = await boardFlight(flight_id, sender.apikey);

    if (resp.status === "error") {
        // Boarding window expired or other error
        send(ws, { type: "ERROR", code: 400, message: resp.data });

        // Notify ATC of no-show
        broadcast({
            type:          "PASSENGER_NO_SHOW",
            flight_id,
            username:      ws._username,
            message:       `Passenger ${ws._username} missed the boarding window for flight ${flight_id}.`,
        }, (u, c) => c.role === "ATC");
        return;
    }

    console.log(`[BOARD] ${ws._username} confirmed boarding on flight ${flight_id}`);

    send(ws, {
        type:    "BOARD_OK",
        flight_id,
        message: "Boarding confirmed!",
    });

    // Notify all ATCs
    broadcast({
        type:          "PASSENGER_BOARDED",
        flight_id,
        username:      ws._username,
        message:       `Passenger ${ws._username} has confirmed boarding for flight ${flight_id}.`,
    }, (u, c) => c.role === "ATC");
}

async function handleTrack(ws, payload) {
    const sender = clients.get(ws._username);
    if (!sender) return send(ws, { type: "ERROR", code: 403, message: "Not authenticated." });

    const { flight_id } = payload;
    if (!flight_id) return send(ws, { type: "ERROR", code: 400, message: "Missing flight_id." });

    // Passengers can only track flights they are booked on
    if (sender.role === "Passenger") {
        const pFlightsResp = await getAllFlights(sender.apikey);
        const booked = (pFlightsResp.data || []).some(f => parseInt(f.id) === parseInt(flight_id));
        if (!booked) {
            return send(ws, { type: "ERROR", code: 403, message: "You are not booked on this flight." });
        }
    }

    // Subscribe to live updates
    if (activeFlights.has(flight_id)) {
        activeFlights.get(flight_id).subscribers.add(ws._username);
        send(ws, {
            type:      "TRACK_OK",
            flight_id,
            message:   `Now tracking flight ${flight_id}. You will receive POSITION updates.`,
        });
    } else {
        // Flight not currently animating – just acknowledge
        send(ws, {
            type:      "TRACK_OK",
            flight_id,
            message:   `Subscribed to flight ${flight_id}. Updates will begin when the flight is In Flight.`,
        });

        //Can add them when animation starts
        if (!global._pendingSubs) global._pendingSubs = new Map();
        if (!global._pendingSubs.has(flight_id)) global._pendingSubs.set(flight_id, new Set());
        global._pendingSubs.get(flight_id).add(ws._username);
    }
}

function handleDisconnect(ws) {
    const username = ws._username;
    if (!username) return;

    const client = clients.get(username);
    if (!client) return;

    console.log(`[DISCONNECT] ${username} (${client.role}) disconnected.`);

    if (client.role === "ATC") {
        // Notify all passengers that ATC briefly disconnected
        broadcast({
            type:    "ATC_DISCONNECTED",
            message: "The ATC operator briefly lost connection. Your flight is unaffected.",
        }, (u, c) => c.role === "Passenger");
    }

    //Remove
    for (const [fid, state] of activeFlights) {
        state.subscribers.delete(username);
    }

    clients.delete(username);
}

function setupCLI(wss) {
    const rl = readline.createInterface({
        input:  process.stdin,
        output: process.stdout,
        prompt: "> ",
    });

    rl.prompt();

    rl.on("line", async (line) => {
        const parts = line.trim().split(/\s+/);
        const cmd   = parts[0] ? parts[0].toUpperCase() : "";

        switch (cmd) {
            case "FLIGHT_STATUS": {
                const flightId = parts[1];
                if (!flightId) { console.log("Usage: FLIGHT_STATUS <flight_id>"); break; }

                const resp = await apiPost({ type: "GetFlight", apikey: SERVER_API_KEY, flight_id: parseInt(flightId) });
                if (resp.status === "error" || !resp.data || !resp.data[0]) {
                    console.log(`[CLI] Flight ${flightId} not found.`);
                    break;
                }
                const f = resp.data[0];
                const state = activeFlights.get(parseInt(flightId));
                const confirmed  = (f.passengers || []).filter(p => p.boarding_confirmed == 1).length;
                const totalPax   = (f.passengers || []).length;
                let etaSecs = "N/A";
                if (state) {
                    const remaining = state.totalTicks - state.currentTick;
                    etaSecs = Math.max(0, Math.round((remaining * TICK_MS) / 1000)) + "s";
                }
                console.log("─────────────────────────────────");
                console.log(`Flight:    ${f.flight_number}`);
                console.log(`Status:    ${f.status}`);
                console.log(`Position:  ${f.current_latitude}, ${f.current_longitude}`);
                console.log(`Boarding:  ${confirmed}/${totalPax} confirmed`);
                console.log(`ETA:       ${etaSecs}`);
                console.log("─────────────────────────────────");
                break;
            }

            case "KILL": {
                const username = parts[1];
                if (!username) { console.log("Usage: KILL <username>"); break; }
                const client = clients.get(username);
                if (!client) { console.log(`[CLI] No connected user: ${username}`); break; }

                send(client.ws, {
                    type:    "KILLED",
                    message: "Your connection has been terminated by the server administrator.",
                });
                setTimeout(() => {
                    try { client.ws.terminate(); } catch (_) {}
                    clients.delete(username);
                    console.log(`[CLI] Killed connection for ${username}`);
                }, 500);
                break;
            }

            case "QUIT": {
                console.log("[CLI] Shutting down server...");
                broadcast({
                    type:    "SHUTDOWN",
                    message: "The server is shutting down. Please reconnect later.",
                });
                setTimeout(() => {
                    wss.close(() => {
                        console.log("[CLI] All connections closed. Goodbye.");
                        process.exit(0);
                    });
                }, 1000);
                break;
            }

            default: {
                if (cmd) {
                    console.log(`Unknown command: ${cmd}`);
                    console.log("Commands: FLIGHT_STATUS <id> | KILL <username> | QUIT");
                }
                break;
            }
        }

        rl.prompt();
    });

    rl.on("close", () => {
        console.log("[CLI] stdin closed.");
    });
}

const MIN_PORT = 1024;
const MAX_PORT = 49151;

function isValidPort(p) {
    return Number.isInteger(p) && p >= MIN_PORT && p <= MAX_PORT;
}

async function promptPort() {
    return new Promise((resolve) => {
        const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
        rl.question(`Enter port (${MIN_PORT}–${MAX_PORT}): `, (answer) => {
            rl.close();
            resolve(parseInt(answer, 10));
        });
    });
}

async function main() {
    let port = parseInt(process.argv[2], 10);

    if (!isValidPort(port)) {
        if (process.argv[2]) {
            console.error(`Invalid port: ${process.argv[2]}. Must be between ${MIN_PORT} and ${MAX_PORT}.`);
        }
        port = await promptPort();
    }

    if (!isValidPort(port)) {
        console.error(`Port ${port} is outside the allowed range (${MIN_PORT}–${MAX_PORT}). Exiting.`);
        process.exit(1);
    }

    const wss = new WebSocket.Server({ port }, () => {
        console.log(`\n Flight Tracker WebSocket Server`);
        console.log(`   Listening on ws://localhost:${port}`);
        console.log(`   API: ${API_URL}`);
        console.log(`   Commands: FLIGHT_STATUS <id> | KILL <username> | QUIT\n`);
    });

    wss.on("connection", (ws) => {
        console.log("[WS] New connection.");

        ws.on("message", async (raw) => {
            let msg;
            try {
                msg = JSON.parse(raw.toString());
            } catch {
                return send(ws, { type: "ERROR", code: 400, message: "Invalid JSON." });
            }

            const type = (msg.type || "").toUpperCase();

            if (type !== "AUTH" && !ws._username) {
                return send(ws, { type: "ERROR", code: 403, message: "Send AUTH first." });
            }

            switch (type) {
                case "AUTH":     await handleAuth(ws, msg);     break;
                case "DISPATCH": await handleDispatch(ws, msg); break;
                case "BOARD":    await handleBoard(ws, msg);    break;
                case "TRACK":    await handleTrack(ws, msg);    break;
                default:
                    send(ws, { type: "ERROR", code: 400, message: `Unknown message type: ${type}` });
            }
        });

        ws.on("close", () => handleDisconnect(ws));
        ws.on("error", (err) => {
            console.error("[WS] Socket error:", err.message);
            handleDisconnect(ws);
        });
    });

    wss.on("error", (err) => {
        console.error("[SERVER] Error:", err.message);
        process.exit(1);
    });

    setupCLI(wss);
}

main().catch((err) => {
    console.error("[FATAL]", err);
    process.exit(1);
});