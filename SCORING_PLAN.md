# 🏆 Credit Flow: Observation & Recovery Scoring Plan

This plan handles "Crossover" cases where a lead was Red Flagged but eventually Converted and performed well.

## 1. Initial Penalty Cap
- **Red Flag on a Converted Lead**: Initial penalty is capped at **-5 Pts**.
- **Reason**: We want to respect caution but still penalize the lack of alignment with the conversion outcome.

## 2. Performance Observation Period
When a lead status changes to `Converted`, the **Performance Observation** starts:

| Repayment Type | Observation Duration | Logic |
| :--- | :--- | :--- |
| **Monthly EMI** | 3 Months | Requires 3 successful monthly installments. |
| **Quarterly** | 6 Months | Requires 2 successful quarterly installments to prove stability. |

---

## 3. The Recovery Algorithm (Bonus)
If the Observation Period is passed successfully (status marked as `Good Performance`):

1.  **Penalty Reversal**: The original staff who gave the Red Flag receives **+10 Pts** (This cancels the -5 and adds +5 net).
2.  **Loyalty Bonus**: An additional **+10 Pts** is awarded for identifying a complex case that eventually contributed to bank growth.
3.  **Total Recovery**: **+20 Pts** total added to the staff's score.

---

## 4. Implementation Steps
1.  **Update Schema**: Add `repayment_type` and `observation_end_date` to the `leads` table.
2.  **Conversion Trigger**: When converting, set the `observation_end_date` based on `repayment_type`.
3.  **Manual/Auto Verify**: Admin can "Validate Performance" after the date passes to trigger the Recovery Bonus.
