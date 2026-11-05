-- Migration: 0005_seed_default_admin.sql
-- Description: Seed initial administrator user (username: admin, email: admin@example.com, password: admin123)

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `created_at`)
VALUES (1, 'admin', 'admin@example.com', '$2y$10$G4dcvCujWfWDiTWwOvJF2eUqKx.X7TQ4kyPsy9ZeqcjcgfGqt8ZTu', NOW())
ON DUPLICATE KEY UPDATE 
  `email` = VALUES(`email`);

