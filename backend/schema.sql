-- Database Schema for Advanced Banking Credit Appraisal System

DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS memos CASCADE;
DROP TABLE IF EXISTS workflows CASCADE;
DROP TABLE IF EXISTS lead_verified_data CASCADE;
DROP TABLE IF EXISTS lead_scoring CASCADE;
DROP TABLE IF EXISTS point_logs CASCADE;
DROP TABLE IF EXISTS leads CASCADE;
DROP TABLE IF EXISTS branch_assignments CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS branches CASCADE;
DROP TABLE IF EXISTS designations CASCADE;
DROP TABLE IF EXISTS provinces CASCADE;

CREATE TABLE provinces (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE designations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    default_power_limit DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE branches (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    short_name VARCHAR(50),
    sol_id VARCHAR(50) UNIQUE NOT NULL,
    location VARCHAR(255),
    province_id INTEGER REFERENCES provinces(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    staff_id VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) CHECK (role IN ('Admin', 'Staff')),
    branch_id INTEGER REFERENCES branches(id),
    province_id INTEGER REFERENCES provinces(id),
    designation VARCHAR(100) REFERENCES designations(name),
    must_reset_password BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE branch_assignments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    branch_id INTEGER REFERENCES branches(id),
    type VARCHAR(20) CHECK (type IN ('permanent', 'temporary')),
    start_date DATE DEFAULT CURRENT_DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE leads (
    id SERIAL PRIMARY KEY,
    lead_id VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_type VARCHAR(20) DEFAULT 'Individual', -- Individual or Business
    contact_number VARCHAR(20),
    relationship_date DATE,
    address TEXT,
    loan_segment VARCHAR(50), -- Retail, SME, MSME, Micro, Agriculture, Guarantee
    loan_type VARCHAR(100), -- New, Renewal, Enhancement, Reduction, Restructure
    loan_scheme VARCHAR(100),
    income_source VARCHAR(100), -- Gov, Private, Business, Agri, Foreign, etc.
    proposed_limit DECIMAL(15, 2) NOT NULL,
    repayment_type VARCHAR(50) DEFAULT 'Monthly',
    is_individual BOOLEAN DEFAULT TRUE,
    profile_completeness INTEGER DEFAULT 0,
    initiator_id INTEGER REFERENCES users(id),
    current_owner_id INTEGER REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'Draft', -- Draft, Analysis, Appraisal, Converted, Ongoing, NPL
    nrb_classification VARCHAR(50) DEFAULT 'Pass', -- Pass, Watch List, Substandard, Doubtful, Loss
    branch_id INTEGER REFERENCES branches(id),
    -- Advanced Qualification Fields
    collateral_type VARCHAR(100),
    estimated_collateral_value DECIMAL(15, 2),
    undivided_family_members INTEGER DEFAULT 1,
    is_pep BOOLEAN DEFAULT FALSE,
    has_legal_dispute BOOLEAN DEFAULT FALSE,
    primary_income DECIMAL(15, 2) DEFAULT 0,
    secondary_income DECIMAL(15, 2) DEFAULT 0,
    other_income_amount DECIMAL(15, 2) DEFAULT 0,
    other_income_source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE workflows (
    id SERIAL PRIMARY KEY,
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    cap_id VARCHAR(50) UNIQUE,
    file_status VARCHAR(50), -- Pending, Analysis, Appraisal, Review, Approved, Defended
    current_step VARCHAR(50),
    assigned_role VARCHAR(50),
    current_handler_id INTEGER REFERENCES users(id),
    power_level_required DECIMAL(15, 2) DEFAULT 0,
    is_locked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lead_reviews (
    id SERIAL PRIMARY KEY,
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    reviewer_id INTEGER REFERENCES users(id),
    level VARCHAR(50), -- Branch, Province, HO
    review_status VARCHAR(50), -- Approved, Declined, Further Discussion, Under Observation
    confidence_level VARCHAR(20), -- Low, Medium, High
    feedback TEXT,
    conditions TEXT, -- Special conditions set by higher authorities
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lead_appraisals (
    id SERIAL PRIMARY KEY,
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    monthly_income DECIMAL(15, 2),
    monthly_expenses DECIMAL(15, 2),
    emi_outflow DECIMAL(15, 2),
    other_bfis_exposure DECIMAL(15, 2),
    fair_market_value DECIMAL(15, 2),
    distress_value DECIMAL(15, 2),
    collateral_location TEXT,
    cra_score INTEGER,
    mitigating_factors TEXT,
    unit_inspection_notes TEXT,
    recommended_limit DECIMAL(15, 2),
    interest_rate DECIMAL(5, 2),
    tenure_months INTEGER,
    appraiser_id INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lead_scoring (
    id SERIAL PRIMARY KEY,
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    lqs_score INTEGER DEFAULT 0, -- Lead Qualification Score (Phase 1)
    sv_score INTEGER DEFAULT 0, -- Staff Verification Score (Phase 2)
    fcs_score INTEGER DEFAULT 0, -- Final Confidence Score
    deviation_percentage DECIMAL(5, 2) DEFAULT 0,
    risk_category VARCHAR(50), -- Low, Moderate, High, Critical
    deviation_alerts JSONB DEFAULT '[]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lead_verified_data (
    id SERIAL PRIMARY KEY,
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    verified_income DECIMAL(15, 2),
    verified_collateral_value DECIMAL(15, 2),
    cib_report_status VARCHAR(50),
    kyc_status VARCHAR(50),
    verification_notes TEXT,
    staff_id INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE point_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    points INTEGER NOT NULL,
    type VARCHAR(50), -- Initiation, Conversion, Monitoring, Deviation_Penalty
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE memos (
    id SERIAL PRIMARY KEY,
    memo_id VARCHAR(100) UNIQUE NOT NULL,
    lead_id VARCHAR(50) REFERENCES leads(lead_id) ON DELETE CASCADE,
    category VARCHAR(50),
    content TEXT,
    creator_id INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    message TEXT NOT NULL,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(255) NOT NULL,
    details JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_leads_lead_id ON leads(lead_id);
CREATE INDEX idx_workflows_lead_id ON workflows(lead_id);
CREATE INDEX idx_scoring_lead_id ON lead_scoring(lead_id);
CREATE INDEX idx_points_user_id ON point_logs(user_id);
