-- Schema of the supplier portal. Column order in products matters.

CREATE TABLE suppliers (
    id serial PRIMARY KEY,
    legal_id text,
    company_name text,
    contact text,
    email text,
    phone text,
    province text
);

CREATE TABLE products (
    id serial PRIMARY KEY,
    name text,
    description text,
    category text,
    price numeric(12,2),
    active boolean
);

CREATE TABLE tenders (
    id serial PRIMARY KEY,
    code text UNIQUE,
    institution text,
    subject text,
    amount numeric(14,2),
    status text,
    closes_on date
);

CREATE TABLE tender_vault (
    id serial PRIMARY KEY,
    code text,
    note text
);

CREATE TABLE users (
    id serial PRIMARY KEY,
    username text UNIQUE,
    full_name text,
    email text,
    role text,
    password_md5 text
);

CREATE TABLE inboxes (
    username text PRIMARY KEY,
    title text,
    content text
);

CREATE TABLE messages (
    id serial PRIMARY KEY,
    name text,
    subject text,
    body text,
    created_at timestamp DEFAULT now()
);

CREATE TABLE reports (
    id serial PRIMARY KEY,
    path text,
    created_at timestamp DEFAULT now(),
    handled boolean DEFAULT false
);

CREATE TABLE invoices (
    id serial PRIMARY KEY,
    file_name text,
    uploaded_at timestamp DEFAULT now()
);

CREATE TABLE secret_settings (
    name text PRIMARY KEY,
    value text
);

CREATE TABLE reviewer_notes (
    name text PRIMARY KEY,
    value text
);
