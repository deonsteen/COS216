-- ============================================================
-- PA4 Database Setup
-- Run this in phpMyAdmin under u25135742_Flights
-- ============================================================

-- Drop in correct order to avoid foreign key issues
DROP TABLE IF EXISTS Passenger_Flights;
DROP TABLE IF EXISTS Flights;
DROP TABLE IF EXISTS Airports;

-- ── 1. AIRPORTS ──────────────────────────────────────────────

CREATE TABLE Airports (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(255)   NOT NULL,
    iata_code VARCHAR(3)     NOT NULL UNIQUE,
    city      VARCHAR(100)   NOT NULL,
    country   VARCHAR(100)   NOT NULL,
    latitude  DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL
);

-- ── 2. FLIGHTS ───────────────────────────────────────────────

CREATE TABLE Flights (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    flight_number          VARCHAR(10)   NOT NULL UNIQUE,
    origin_airport_id      INT           NOT NULL,
    destination_airport_id INT           NOT NULL,
    departure_time         DATETIME      NOT NULL,
    flight_duration_hours  DECIMAL(5, 2) NOT NULL,
    status                 ENUM('Scheduled', 'Boarding', 'In Flight', 'Landed') NOT NULL DEFAULT 'Scheduled',
    current_latitude       DECIMAL(10, 6) NOT NULL,
    current_longitude      DECIMAL(10, 6) NOT NULL,
    dispatched_at          DATETIME      NULL DEFAULT NULL,
    CONSTRAINT fk_flights_origin      FOREIGN KEY (origin_airport_id)      REFERENCES Airports(id),
    CONSTRAINT fk_flights_destination FOREIGN KEY (destination_airport_id) REFERENCES Airports(id)
);

-- ── 3. PASSENGER_FLIGHTS ─────────────────────────────────────

CREATE TABLE Passenger_Flights (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id       INT        NOT NULL,
    flight_id          INT        NOT NULL,
    seat_number        VARCHAR(5) NULL DEFAULT NULL,
    boarding_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    confirmed_at       DATETIME   NULL DEFAULT NULL,
    CONSTRAINT fk_pf_passenger FOREIGN KEY (passenger_id) REFERENCES Users(id),
    CONSTRAINT fk_pf_flight    FOREIGN KEY (flight_id)    REFERENCES Flights(id)
);

-- ── 4. SEED AIRPORTS ─────────────────────────────────────────

INSERT INTO Airports (name, iata_code, city, country, latitude, longitude) VALUES
('OR Tambo International Airport',        'JNB', 'Johannesburg', 'South Africa',        -26.136700,  28.241100),
('Cape Town International Airport',       'CPT', 'Cape Town',    'South Africa',        -33.964800,  18.591700),
('King Shaka International Airport',      'DUR', 'Durban',       'South Africa',        -29.614400,  31.119700),
('Heathrow Airport',                      'LHR', 'London',       'United Kingdom',       51.470000,  -0.454300),
('John F. Kennedy International Airport', 'JFK', 'New York',     'United States',        40.641300, -73.778100),
('Charles de Gaulle Airport',             'CDG', 'Paris',        'France',               49.009700,   2.547900),
('Dubai International Airport',           'DXB', 'Dubai',        'United Arab Emirates', 25.253200,  55.365700),
('Sydney Kingsford Smith Airport',        'SYD', 'Sydney',       'Australia',           -33.939900, 151.175300),
('Los Angeles International Airport',     'LAX', 'Los Angeles',  'United States',        33.942500,-118.408100),
('Jomo Kenyatta International Airport',   'NBO', 'Nairobi',      'Kenya',                -1.319200,  36.927500),
('Singapore Changi Airport',              'SIN', 'Singapore',    'Singapore',             1.364400, 103.991500),
('Hong Kong International Airport',       'HKG', 'Hong Kong',    'China',                22.308000, 113.918500);

-- ── 5. SEED FLIGHTS ──────────────────────────────────────────
-- Airport IDs: 1=JNB 2=CPT 3=DUR 4=LHR 5=JFK
--              6=CDG 7=DXB 8=SYD 9=LAX 10=NBO 11=SIN 12=HKG

INSERT INTO Flights (flight_number, origin_airport_id, destination_airport_id, departure_time, flight_duration_hours, status, current_latitude, current_longitude, dispatched_at) VALUES
('SA203',  1,  4, '2026-05-20 08:00:00', 11.50, 'Scheduled', -26.136700,  28.241100, NULL),
('SA302',  2,  1, '2026-05-19 14:00:00',  2.00, 'Scheduled', -33.964800,  18.591700, NULL),
('BA017',  4,  5, '2026-05-21 09:30:00',  7.50, 'Scheduled',  51.470000,  -0.454300, NULL),
('EK763',  7,  4, '2026-05-22 02:00:00',  7.00, 'Scheduled',  25.253200,  55.365700, NULL),
('QF001',  8,  4, '2026-05-23 16:00:00', 21.50, 'Scheduled', -33.939900, 151.175300, NULL),
('AF447',  6,  5, '2026-05-24 10:15:00',  8.00, 'Scheduled',  49.009700,   2.547900, NULL),
('KQ101', 10,  1, '2026-05-25 06:30:00',  3.50, 'Scheduled',  -1.319200,  36.927500, NULL),
('SQ321', 11,  4, '2026-05-26 23:00:00', 13.00, 'Scheduled',   1.364400, 103.991500, NULL),
('CX234', 12, 11, '2026-05-27 08:00:00',  4.00, 'Scheduled',  22.308000, 113.918500, NULL),
('SA101',  1,  2, '2026-05-18 10:00:00',  2.00, 'Boarding',  -26.136700,  28.241100, NOW()),
('AA100',  5,  9, '2026-05-17 12:00:00',  5.50, 'In Flight',  37.500000,-100.000000, NULL),
('DL401',  9,  5, '2026-05-17 07:45:00',  5.00, 'Landed',     40.641300, -73.778100, NULL);
