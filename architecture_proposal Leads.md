# Advanced Credit Flow: Dual-Scoring Engine & Lead Redesign

This proposal focuses on the **Lead Module Redesign** and the implementation of a **Dual-Scoring Engine** to measure both Staff Performance and Borrower Creditworthiness.

## 1. The Redesigned Lead Lifecycle
A Lead is no longer just a "form"; it's a dynamic entity that matures into a Borrower.

| Stage | Activity | Staff Action | System Impact |
| :--- | :--- | :--- | :--- |
| **Generation** | RM/Assistant identifies a prospect. | Log Basic Info | +10 Points (Staff) |
| **Analysis** | Deep dive into income, family, and history. | Fill Analysis Form | Discussion Points |
| **Appraisal** | Writing the credit memo. | Structure Data | - |
| **Conversion** | Final Approval by CH/PH. | Decision Made | High Points (Staff/RM) |
| **Monitoring** | Post-disbursement (Quarterly). | Review Performance | Recurring LP (Staff) |

## 2. Dual-Scoring Engine Details

### A. Staff Scoring (Loyalty & Efficiency)
Designed to reward proactive and accurate behavior.
1.  **Initiation**: +10 LP (Initial effort).
2.  **Interaction Quality**: 
    *   Meaningful Discussion: +2 to +5 LP.
    *   Escalation to Higher Authority: +2 LP.
3.  **Conversion Bonus**:
    *   If Lead is Approved: +50 LP to RM/Initiator.
    *   If Lead is Rejected: -10 LP (to discourage bad leads).
4.  **Long-term Monitoring (THE INNOVATION)**:
    *   Quarterly check on borrower repayment.
    *   If Borrower is "Good": +10 LP to RM (even if they have moved roles).
    *   If Borrower is "Bad/NPL": -25 LP (Penalty for poor initial analysis).

### B. Borrower Scoring (Client Credit Score - CCS)
Mathematical analysis of risk (Scale: 1-100).
- **Repayment History (30%)**: Past loans, internal behavior.
- **Income Stability (25%)**: Multiple sources, secondary income.
- **Banking Knowledge (15%)**: Customer sophistication level.
- **Family Background (10%)**: Stability indicators.
- **Assets/Liabilities (20%)**: Net worth and leverage.

## 3. Departmental "Repayment Monitoring"
A specialized view for the **Monitoring Department** to log quarterly repayment status, which automatically triggers the Staff Scoring updates.

## 4. Architectural Updates

### [NEW] `staff_scoring_audit` table
- `user_id`, `lead_id`
- `points_type` (Initial, Quality, Conversion, Monitoring)
- `points_awarded`
- `quarter_period` (For monitoring)

### [NEW] `borrower_financials` table
Extends the lead analysis with deep financial ratios.

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
