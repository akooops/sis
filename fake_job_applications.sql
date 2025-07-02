-- Fake Job Applications Data for Job Posting ID 2
-- Insert sample job applications with realistic data

INSERT INTO job_applications (
    job_posting_id,
    first_name,
    last_name,
    email,
    phone,
    nationality,
    ai_score,
    created_at,
    updated_at
) VALUES
-- High-scoring candidates (8-10)
(2, 'Sarah', 'Johnson', 'sarah.johnson@email.com', '+1-555-0101', 'American', 9.2, '2024-01-15 09:30:00', '2024-01-15 09:30:00'),
(2, 'Ahmed', 'Al-Rashid', 'ahmed.alrashid@email.com', '+966-50-123-4567', 'Saudi', 8.8, '2024-01-16 14:22:00', '2024-01-16 14:22:00'),
(2, 'Emily', 'Chen', 'emily.chen@email.com', '+1-555-0102', 'Canadian', 8.5, '2024-01-17 11:15:00', '2024-01-17 11:15:00'),
(2, 'Mohammed', 'Khalil', 'mohammed.khalil@email.com', '+971-50-987-6543', 'Emirati', 8.1, '2024-01-18 16:45:00', '2024-01-18 16:45:00'),

-- Good candidates (6-7.9)
(2, 'David', 'Thompson', 'david.thompson@email.com', '+1-555-0103', 'British', 7.8, '2024-01-19 10:20:00', '2024-01-19 10:20:00'),
(2, 'Fatima', 'Zahra', 'fatima.zahra@email.com', '+966-55-234-5678', 'Saudi', 7.5, '2024-01-20 13:30:00', '2024-01-20 13:30:00'),
(2, 'James', 'Wilson', 'james.wilson@email.com', '+1-555-0104', 'Australian', 7.2, '2024-01-21 08:45:00', '2024-01-21 08:45:00'),
(2, 'Aisha', 'Hassan', 'aisha.hassan@email.com', '+966-54-345-6789', 'Saudi', 6.9, '2024-01-22 15:10:00', '2024-01-22 15:10:00'),
(2, 'Michael', 'Brown', 'michael.brown@email.com', '+1-555-0105', 'American', 6.7, '2024-01-23 12:25:00', '2024-01-23 12:25:00'),
(2, 'Layla', 'Abdullah', 'layla.abdullah@email.com', '+966-56-456-7890', 'Saudi', 6.3, '2024-01-24 09:40:00', '2024-01-24 09:40:00'),

-- Average candidates (5-5.9)
(2, 'Robert', 'Davis', 'robert.davis@email.com', '+1-555-0106', 'Canadian', 5.8, '2024-01-25 14:15:00', '2024-01-25 14:15:00'),
(2, 'Noor', 'Al-Sayed', 'noor.alsayed@email.com', '+966-57-567-8901', 'Saudi', 5.5, '2024-01-26 11:30:00', '2024-01-26 11:30:00'),
(2, 'Jennifer', 'Garcia', 'jennifer.garcia@email.com', '+1-555-0107', 'American', 5.2, '2024-01-27 16:20:00', '2024-01-27 16:20:00'),
(2, 'Omar', 'Rashid', 'omar.rashid@email.com', '+966-58-678-9012', 'Saudi', 5.1, '2024-01-28 10:35:00', '2024-01-28 10:35:00'),

-- Lower scoring candidates (3-4.9)
(2, 'Christopher', 'Martinez', 'christopher.martinez@email.com', '+1-555-0108', 'Mexican', 4.8, '2024-01-29 13:50:00', '2024-01-29 13:50:00'),
(2, 'Yasmin', 'Al-Zahra', 'yasmin.alzahra@email.com', '+966-59-789-0123', 'Saudi', 4.5, '2024-01-30 08:55:00', '2024-01-30 08:55:00'),
(2, 'Daniel', 'Anderson', 'daniel.anderson@email.com', '+1-555-0109', 'British', 4.2, '2024-01-31 15:25:00', '2024-01-31 15:25:00'),
(2, 'Mariam', 'Khalid', 'mariam.khalid@email.com', '+966-50-890-1234', 'Saudi', 3.9, '2024-02-01 12:10:00', '2024-02-01 12:10:00'),

-- Pending AI scores (no score yet)
(2, 'William', 'Taylor', 'william.taylor@email.com', '+1-555-0110', 'American', NULL, '2024-02-02 09:15:00', '2024-02-02 09:15:00'),
(2, 'Hana', 'Al-Mansour', 'hana.almansour@email.com', '+966-51-901-2345', 'Saudi', NULL, '2024-02-03 14:40:00', '2024-02-03 14:40:00'),
(2, 'Joseph', 'Thomas', 'joseph.thomas@email.com', '+1-555-0111', 'Canadian', NULL, '2024-02-04 11:05:00', '2024-02-04 11:05:00'),
(2, 'Reem', 'Al-Otaibi', 'reem.alotaibi@email.com', '+966-52-012-3456', 'Saudi', NULL, '2024-02-05 16:30:00', '2024-02-05 16:30:00'),

-- Additional recent applications
(2, 'Andrew', 'Jackson', 'andrew.jackson@email.com', '+1-555-0112', 'American', 7.1, '2024-02-06 10:20:00', '2024-02-06 10:20:00'),
(2, 'Dana', 'Al-Sharif', 'dana.alsharif@email.com', '+966-53-123-4567', 'Saudi', 6.8, '2024-02-07 13:45:00', '2024-02-07 13:45:00'),
(2, 'Ryan', 'White', 'ryan.white@email.com', '+1-555-0113', 'Australian', 8.3, '2024-02-08 08:30:00', '2024-02-08 08:30:00'),
(2, 'Zahra', 'Al-Qahtani', 'zahra.alqahtani@email.com', '+966-54-234-5678', 'Saudi', 7.9, '2024-02-09 15:15:00', '2024-02-09 15:15:00'),
(2, 'Kevin', 'Harris', 'kevin.harris@email.com', '+1-555-0114', 'British', 5.7, '2024-02-10 12:00:00', '2024-02-10 12:00:00'),
(2, 'Lina', 'Al-Dossary', 'lina.aldossary@email.com', '+966-55-345-6789', 'Saudi', 6.2, '2024-02-11 09:25:00', '2024-02-11 09:25:00'),

-- More diverse international candidates
(2, 'Carlos', 'Rodriguez', 'carlos.rodriguez@email.com', '+52-55-1234-5678', 'Mexican', 7.4, '2024-02-12 14:50:00', '2024-02-12 14:50:00'),
(2, 'Priya', 'Patel', 'priya.patel@email.com', '+91-98765-43210', 'Indian', 8.7, '2024-02-13 11:35:00', '2024-02-13 11:35:00'),
(2, 'Hans', 'Mueller', 'hans.mueller@email.com', '+49-30-12345678', 'German', 7.6, '2024-02-14 16:20:00', '2024-02-14 16:20:00'),
(2, 'Yuki', 'Tanaka', 'yuki.tanaka@email.com', '+81-3-1234-5678', 'Japanese', 8.9, '2024-02-15 10:45:00', '2024-02-15 10:45:00'),
(2, 'Pierre', 'Dubois', 'pierre.dubois@email.com', '+33-1-2345-6789', 'French', 6.5, '2024-02-16 13:10:00', '2024-02-16 13:10:00'),

-- Recent applications with pending scores
(2, 'Sofia', 'Silva', 'sofia.silva@email.com', '+55-11-98765-4321', 'Brazilian', NULL, '2024-02-17 08:55:00', '2024-02-17 08:55:00'),
(2, 'Ali', 'Al-Hamdan', 'ali.alhamdan@email.com', '+966-56-456-7890', 'Saudi', NULL, '2024-02-18 15:30:00', '2024-02-18 15:30:00'),
(2, 'Elena', 'Ivanova', 'elena.ivanova@email.com', '+7-495-123-4567', 'Russian', NULL, '2024-02-19 12:05:00', '2024-02-19 12:05:00'),
(2, 'Nour', 'Al-Shehri', 'nour.alshehri@email.com', '+966-57-567-8901', 'Saudi', NULL, '2024-02-20 09:40:00', '2024-02-20 09:40:00');

-- Note: This creates 35 job applications with varying AI scores and nationalities
-- The data includes:
-- - 4 high-scoring candidates (8-10)
-- - 6 good candidates (6-7.9)
-- - 4 average candidates (5-5.9)
-- - 4 lower scoring candidates (3-4.9)
-- - 8 pending AI scores
-- - 9 additional diverse candidates
-- 
-- Nationalities include: Saudi, American, Canadian, British, Australian, Emirati, Mexican, Indian, German, Japanese, French, Brazilian, Russian
-- Phone numbers follow international formats
-- Dates span from January 15 to February 20, 2024 