# Advanced Credit Flow: Architectural Analysis & Proposal

This document outlines the expansion of the current "Credit Flow" system into a sophisticated banking-grade Credit Analysis platform.

## Current State Analysis
- **Lead Module**: Basic data collection. Acts as a trigger for workflow.
- **Workflow**: Linear escalation (Staff -> BM -> PH -> CH).
- **Scoring**: Focused on staff performance (Confidence/Loyalty Points).
- **Missing**: Detailed customer analysis, structured appraisals, and document generation (Offer Letters).

## Proposed Architecture expansion

### 1. Advanced Lead Analysis (The "Analyst" Module)
We will transform the Lead module from a simple form into a **Customer Analysis Hub**.
- **New Data Points**:
    - **Demographics**: Family status, dependents, education.
    - **Economic Info**: Multiple income sources (Salary, Rental, Business), Asset valuation.
    - **Financial Behavior**: Banking knowledge (level of sophistication), Repayment history (Internal + CIB data mock).
- **Dynamic Scoring**: A new engine will assign a **Client Credit Score (CCS)** based on these weighted factors.

### 2. Appraisal Writing Module
This is where the actual credit decision logic resides.
- **Structure**: Qualitative and Quantitative assessment.
- **Implementation**: A multi-section form that compiles data from the Lead analysis and allows the Credit Officer to write their recommendation.
- **Persistence**: Save appraisals linked to `lead_id`.

### 3. Workflow v2
- **Stages**:
    - **Lead Generation**: RM collects basic info.
    - **Analysis**: Analyst/RM adds advanced details.
    - **Appraisal Writing**: Structural analysis of the business/income.
    - **Review/Approval**: Existing escalation flow (In-Power or Forwarded).
    - **Offer Generation**: Triggered upon "Final Approval".

### 4. Offer Letter Generator
- **Tech Stack**: Use `pdfkit` (backend) to generate professional bank-branded offer letters.
- **Workflow**: Only accessible after lead status is `Converted/Approved`.

## Database Schema Updates (Proposed)

### [NEW] `lead_analysis` table
- `lead_id`
- `income_sources` (JSON/Text)
- `family_background` (Text)
- `banking_knowledge` (Enum: Basic, Intermediate, Advanced)
- `repayment_history_internal` (JSON - list of past payment statuses)
- `assets_details` (JSON)
- `liabilities_details` (JSON)
- `client_credit_score` (Integer)

### [NEW] `appraisals` table
- [id](file:///d:/Credit%20Flow/backend/src/controllers/leadController.js#547-579), `lead_id`
- `summary_recommendation` (Text)
- `risk_factors` (Text)
- `mitigating_factors` (Text)
- `writer_id` (User ID)
- `status` (Draft/Finalized)

## User Experience (UI)
- **Dashboard**: High-level metrics on lead quality distribution.
- **Analysis Interface**: Intuitive tabs for demographics, income, and history.
- **Appraisal Editor**: A clean, distraction-free environment for writing reports.
