-- Development-only: set the admin password to a known value (admin123)
-- so the app is usable immediately in the Base44 dev environment.
UPDATE admins
SET password_hash = '$2y$10$Q99WRmi9JDDbzhcLUoC6XeMNnKlkz1q0I.BD0.RDAuQWCdmUSw.Ze'
WHERE email = 'ibrabra651@gmail.com';
