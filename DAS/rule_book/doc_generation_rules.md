# Document Generation Rule Book

This document defines the complex logic required for generating legal documents based on user input, collateral details, and guarantor configurations.

## 1. Mortgage Deed & Rokka Letter (Land Administration)
**Rule:** Documents must be grouped by the unique combination of **Land Owner** and **Malpot Office** (Land Revenue Office).

-   **Scenario A: Same Owner, Same Malpot Office**
    -   **result:** Generate **1 Mortgage Deed** and **1 Rokka Letter**.
    -   *Details:* All land parcels belonging to this owner at this specific office are listed in a single document.

-   **Scenario B: Same Owner, Different Malpot Offices**
    -   **result:** Generate **Multiple Mortgage Deeds** and **Multiple Rokka Letters** (One pair per Malpot Office).
    -   *Details:* Documents are segregated by jurisdiction.

-   **Scenario C: Different Owners**
    -   **result:** Separate documents for each owner (further split by Malpot Office if applicable).

## 2. Personal Guarantee Deed
**Rule:** One document per individual guarantor.

-   **Scope:** Iterate through every person listed in the **Guarantor** section.
-   **Result:** Generate a separate "Personal Guarantee Deed" for each person.

## 3. Power of Attorney (POA)
**Rule:** One document per individual involved in the loan (Borrowers + Guarantors).

-   **Scope:** Iterate through **All Borrowers** AND **All Guarantors**.
-   **Result:** Generate a separate "Power of Attorney" for each person.

## 4. Demand Promissory Note (I & II)
**Rule:** A single aggregated document listing everyone.

-   **Scope:** All Borrowers and All Guarantors.
-   **Result:** Generate **1 Demand Promissory Note** (and/or I & II) that lists all names and signatures in a single document.

## 5. Loan Deed
**Rule:** A single master document.

-   **Scope:** The main loan agreement.
-   **Result:** Generate **1 Loan Deed**.

## 6. Consent for Legal Heirs (New Feature)
**Rule:** Conditional generation based on specific collateral requirements.

-   **Trigger:** A checkbox "Legal Heir Applicable?" in the Collateral Section.
-   **Data Source:** A new section "Legal Heirs" (capturing Name, Relation, etc.) linked to the Collateral/Owner.
-   **Result:**
    -   If Checkbox is **CHECKED**: Generate **1 Consent Deed** (or one per heir, TBD - assuming one consent deed signed by all heirs usually, or per owner's set of heirs). *Clarification: Usually consent is given by heirs of the property owner.*

## 7. Nepali Legal Identification Logic
**Rule:** Generate a single continuous Nepali legal identification paragraph strictly based on provided inputs.

### Inputs Required
- Family: Gender, Marital Status, Grandfather, Father/Mother, Father-in-law (if married female), Spouse (if married).
- Address: Permanent (District, Municipality, Ward), Current (District, Municipality, Ward).
- Citizenship: Number, Issue Date, Authority Type (DAO/AAO), Issue District, Re-issue Status/Date/Type.

### Generation Logic

#### A. Family Introduction Rules
1. **Male Unmarried**: `{GF}` ko naati, `{FATHER}` ko chhora
2. **Male Married**: `{GF}` ko naati, `{FATHER}` ko chhora, `{SPOUSE}` ki pati
3. **Female Unmarried**: `{GF}` ko naatini, `{FATHER}` ko chhori
4. **Female Married**: `{FIL}` ko buhari, `{FATHER}` ko chhori, `{SPOUSE}` ko patni

#### B. Address Format
Must follow exact format:
`{P_DIST}` jilla `{P_MUNI}` wada no `{P_WARD}` sthayi thegana bhai haal `{C_DIST}` jilla `{C_MUNI}` wada no `{C_WARD}` basne

#### C. Citizenship Statement Rules
Base Format:
`(ना.प्र.नं {CIT_NO}, {ISSUE_DATE} मा {AUTHORITY} {ISSUE_DIST} बाट जारी`

**Authority Mapping:**
- District Administration Office -> "जि.प्र.का"
- Area Administration Office -> "ई.प्र.का"

**Re-issue Logic:**
- If **Re-issue = Yes**: Append `भई मिति {REISSUE_DATE} मा {COPY_TYPE} प्रतिलिपि जारी` before closure.
- If **Re-issue = No**: Do NOT add any extra text.

**Closure:**
End with `)`

#### D. Final Output Construction
Combine parts into one single continuous paragraph:
`[Family Intro], [Address] [Age] barsha ko/ki [Name] [Citizenship Statement]` (Note: Exact joining grammar to be handled by code).

### Implementation Matrix Summary

| Document Type | Generation Entity Scope | Grouping Factor | Quantity |
| :--- | :--- | :--- | :--- |
| **Mortgage Deed** | Collateral (Land) | Owner + Malpot Office | 1 per (Owner + Malpot) |
| **Rokka Letter** | Collateral (Land) | Owner + Malpot Office | 1 per (Owner + Malpot) |
| **Personal Guarantee** | Guarantors | Individual Person | 1 per Guarantor |
| **Power of Attorney** | Borrower + Guarantor | Individual Person | 1 per Person (All) |
| **Promissory Note** | Borrower + Guarantor | All (Aggregated) | 1 Single Document |
| **Loan Deed** | Loan Profile | Single | 1 Single Document |
| **Legal Heir Consent**| Legal Heirs | Owner (if applicable) | 1 per Owner (with heirs) |

## Implementation Data Needs (Updated)

### Templates Needed
-   [ ] Mortgage Deed Template
-   [ ] Rokka Letter Template
-   [ ] Personal Guarantee Template
-   [ ] Power of Attorney Template
-   [ ] Demand Promissory Template
-   [ ] Loan Deed Template
-   [ ] Legal Heir Consent Template
