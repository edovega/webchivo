-- Synthetic, deterministic seed data. No randomness. All names are fictional.

-- 40 suppliers
INSERT INTO suppliers (legal_id, company_name, contact, email, phone, province)
SELECT
    '3-999-' || lpad((100000 + n)::text, 6, '0'),
    (ARRAY['Distribuidora', 'Comercializadora', 'Servicios', 'Importadora', 'Industrias'])[1 + (n - 1) % 5]
        || ' '
        || (ARRAY['Ejemplo', 'Ficticia', 'Modelo', 'Demostrativa', 'Prueba', 'Simulada', 'Imaginaria', 'Ilustrativa'])[1 + (n - 1) / 5]
        || ' ' || lpad(n::text, 2, '0') || ' S.A.',
    'Contacto Ficticio ' || lpad(n::text, 2, '0'),
    'proveedor' || lpad(n::text, 2, '0') || '@proveedores.test',
    '+506 0000-00' || lpad(n::text, 2, '0'),
    (ARRAY['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'])[1 + (n - 1) % 7]
FROM generate_series(1, 40) AS n;

-- 80 products, 20 per category, five inactive
INSERT INTO products (name, description, category, price, active)
SELECT
    (CASE (n - 1) / 20
        WHEN 0 THEN (ARRAY['Martillo', 'Destornillador', 'Cinta métrica', 'Llave ajustable', 'Caja de tornillos'])[1 + (n - 1) % 5]
        WHEN 1 THEN (ARRAY['Resma de papel', 'Bolígrafos', 'Carpetas', 'Engrapadora', 'Archivador'])[1 + (n - 1) % 5]
        WHEN 2 THEN (ARRAY['Desinfectante', 'Detergente', 'Escoba', 'Bolsas de basura', 'Guantes de limpieza'])[1 + (n - 1) % 5]
        ELSE (ARRAY['Teclado', 'Ratón', 'Monitor', 'Cable de red', 'Disco externo'])[1 + (n - 1) % 5]
     END)
        || ' ' || (ARRAY['básico', 'estándar', 'reforzado', 'profesional'])[1 + ((n - 1) % 20) / 5],
    'Artículo para uso institucional, referencia CV-' || lpad(n::text, 4, '0') || '.',
    (ARRAY['Ferreteria', 'Oficina', 'Limpieza', 'Computo'])[1 + (n - 1) / 20],
    ((n * 3779) % 95000 + 1250)::numeric(12,2),
    n NOT IN (7, 23, 41, 58, 76)
FROM generate_series(1, 80) AS n;

-- 25 tenders from fictional public institutions
INSERT INTO tenders (code, institution, subject, amount, status, closes_on)
SELECT
    'LIC-2026-' || lpad(n::text, 3, '0'),
    (ARRAY[
        'Instituto Nacional de Ejemplo',
        'Municipalidad de Villa Ficticia',
        'Hospital Regional de Prueba',
        'Universidad Estatal Modelo',
        'Consejo Nacional Demostrativo'
    ])[1 + (n - 1) % 5],
    (ARRAY[
        'Compra de materiales de ferretería',
        'Suministro de papelería y útiles de oficina',
        'Servicio de limpieza de instalaciones',
        'Adquisición de equipo de cómputo',
        'Mantenimiento de mobiliario'
    ])[1 + (n * 2) % 5],
    ((n * 1234567) % 90000000 + 2500000)::numeric(14,2),
    (ARRAY['Abierta', 'En evaluación', 'Adjudicada', 'Cerrada'])[1 + (n - 1) % 4],
    DATE '2026-01-12' + (n * 11)
FROM generate_series(1, 25) AS n;

-- 6 users. password_md5 is unsalted md5(password) on purpose, written as
-- precomputed hex so this file does not reveal the passwords.
INSERT INTO users (username, full_name, email, role, password_md5) VALUES
    ('admin',     'Administración del Portal',  'admin@cerroverde.test',     'admin',      '0c431b19242090bfb97ce2ebf6f4cd6e'),
    ('auditoria', 'Auditoría Interna',          'auditoria@cerroverde.test', 'auditor',    '0571749e2ac330a7455809c6b0e7af90'),
    ('compras',   'Departamento de Compras',    'compras@cerroverde.test',   'purchasing', '7c6a180b36896a0a8c02787eeafb0e4c'),
    ('finanzas',  'Departamento de Finanzas',   'finanzas@cerroverde.test',  'operator',   'c99204948ac070e3873c2795eb084a8b'),
    ('bodega',    'Bodega Central',             'bodega@cerroverde.test',    'operator',   'd9571481769207484685de4e51be2363'),
    ('soporte',   'Soporte Técnico',            'soporte@cerroverde.test',   'operator',   'f20d7e4c1ccfb0da5951c80c67cf89e6');

-- 3 harmless initial messages
INSERT INTO messages (name, subject, body, created_at) VALUES
    ('Distribuidora Ejemplo 01 S.A.', 'Consulta sobre orden de compra', '¿Podrían confirmar la fecha de entrega del pedido PED-1001?', TIMESTAMP '2026-02-03 09:15:00'),
    ('Servicios Ficticia 08 S.A.', 'Actualización de datos', 'Les informamos el cambio de la persona de contacto de nuestra empresa.', TIMESTAMP '2026-02-05 14:40:00'),
    ('Importadora Modelo 14 S.A.', 'Factura pendiente', 'Adjuntamos nuevamente la factura enviada en enero.', TIMESTAMP '2026-02-09 11:05:00');

-- Portal settings. Flag rows are added by 04_flags.sh.
INSERT INTO secret_settings (name, value) VALUES
    ('portal_version', '2.4.1'),
    ('support_email', 'soporte@cerroverde.test');
