# DAS Complete Implementation - Final Setup Guide

## 🎯 Quick Start (5 Steps)

### **Step 1: Run Setup Script**
```bash
# Open Command Prompt as Administrator
cd c:\xampp\htdocs\Credit\DAS
setup.bat
```

This will:
- Install PHPWord automatically
- Create required directories
- Verify setup

### **Step 2: Import Database Files (3 SQL files)**

Open phpMyAdmin (`http://localhost/phpmyadmin`), select `das_db`, and import in this order:

1. **`master_architecture_migration.sql`** - Fixes schema
2. **`workflow_enhancements.sql`** - Adds workflow features  
3. **`comprehensive_placeholders.sql`** - Adds all placeholders

### **Step 3: Verify Installation**

Run this SQL to check:
```sql
-- Check if everything is installed
SHOW TABLES LIKE '%profile%';
SHOW TABLES LIKE '%master%';
SELECT COUNT(*) FROM template_placeholders;
```

Expected results:
- `profile_documents` table exists
- `master_borrowers` table exists
- `master_guarantors` table exists
- 150+ placeholders

### **Step 4: Create Sample Master Template**

1. Open Microsoft Word
2. Create a simple template:

```
LOAN AGREEMENT

Date: ${current_date}

BORROWER DETAILS:
┌────────────────────────────────────────┐
│ S.N │ Name             │ Citizenship   │
├────────────────────────────────────────┤
│${sn} │${borrower_name}  │${borrower_cit}│
└────────────────────────────────────────┘

LOAN AMOUNT: Rs. ${loan_amount}
INTEREST RATE: ${interest_rate}%
```

3. Save as `loan_agreement.docx`
4. Upload via Admin → DAS Template Garage

### **Step 5: Test the System**

1. Login as Maker
2. Create customer profile:
   - Add borrower (check "Co-Borrower" for additional borrowers)
   - Add guarantor
   - Add collateral
   - Add loan details → **Select Loan Scheme**
   - Submit
   
3. Login as Checker → Approve

4. Login as Maker → Click "Generate Documents"
   - Select template
   - Generate
   - Download and verify!

---

## 📁 Complete File Structure

```
Credit/
└── DAS/
    ├── database/
    │   ├── master_architecture_migration.sql ✅
    │   ├── workflow_enhancements.sql ✅
    │   └── comprehensive_placeholders.sql ✅
    ├── includes/
    │   ├── document_generation.php ✅
    │   ├── profile_locking.php ✅
    │   ├── comment_functions.php ✅
    │   ├── borrower_master.php ✅
    │   └── search_modals.php ✅
    ├── modules/
    │   ├── api/
    │   │   ├── document_generation_api.php ✅
    │   │   ├── pick_profile_api.php ✅
    │   │   ├── approve_profile_api.php ✅
    │   │   ├── return_profile_api.php ✅
    │   │   ├── add_comment_api.php ✅
    │   │   ├── search_borrower_api.php ✅
    │   │   ├── search_guarantor_api.php ✅
    │   │   ├── get_master_borrower.php ✅
    │   │   └── get_master_guarantor.php ✅
    │   ├── maker/
    │   │   └── generate_documents.php ✅
    │   └── checker/
    │       └── (enhanced dashboard - optional)
    ├── generated/ (auto-created)
    ├── temp/ (auto-created)
    ├── vendor/ (composer creates)
    └── setup.bat ✅
```

---

## ✅ All Features Implemented

### **Document Generation**
- ✅ PHPWord integration
- ✅ Dynamic row cloning for borrowers/guarantors/collateral
- ✅ Master template system
- ✅ No hardcoded placeholders
- ✅ Beautiful UI for generation
- ✅ Download generated documents

### **Workflow Enhancements**
- ✅ Profile status tracking (Draft → Submitted → Picked → Approved)
- ✅ Profile locking (30-min timeout)
- ✅ Comments system (section-specific)
- ✅ Borrower/Guarantor reuse (master records)
- ✅ Search functionality with autocomplete

### **Database Architecture**
- ✅ Fixed schema (loan_details.scheme_id instead of template_id)
- ✅ Templates only link when generated
- ✅ Stored procedures for operations
- ✅ Views for easy querying
- ✅ 150+ standardized placeholders

---

## 🎬 Usage Examples

### **Example 1: Creating Profile with Co-Borrowers**

```
1. Maker adds Main Borrower:
   Name: Rajesh Kumar
   Type: Individual
   ☐ Co-Borrower

2. Maker clicks "Add Another Borrower":
   Name: Sita Sharma (wife)
   Type: Individual
   ☑ Co-Borrower

3. System stores both in borrowers table:
   - Row 1: Rajesh Kumar, is_co_borrower = 0
   - Row 2: Sita Sharma, is_co_borrower = 1

4. On document generation:
   Borrower table shows:
   | 1 | Rajesh Kumar | ... |
   | 2 | Sita Sharma  | ... |
```

### **Example 2: Reusing Borrower**

```
1. Borrower "Rajesh Kumar" already in master
2. Creating new profile → Click "Search Existing Borrower"
3. Type "Rajesh" → Shows in search results
4. Click → Form auto-fills
5. Save → Links to master record
6. master_borrowers.usage_count increments
```

### **Example 3: Generating Multiple Documents**

```
1. Profile approved
2. Click "Generate Documents"
3. Select:
   ☑ Loan Agreement
   ☑ Sanction Letter
   ☑ Guarantor Form
4. Click "Generate"
5. System creates 3 DOCX files
6. All downloadable from sidebar
```

---

## 🔧 Configuration

### **Update Placeholder Values**

File: `DAS/includes/document_generation.php`

```php
// Update bank details
function fillSystemPlaceholders($processor, $profile) {
    // Change these values:
    $processor->setValue('bank_name', 'YOUR BANK NAME');
    $processor->setValue('bank_address', 'YOUR ADDRESS');
    $processor->setValue('bank_branch', 'YOUR BRANCH');
}
```

### **Update Number-to-Words**

For proper English/Nepali conversion, install library:
```bash
composer require kwn/number-to-words
```

Then update `numberToWords()` function in `document_generation.php`.

---

## 🎯 Production Checklist

Before going live:

- [ ] Import all 3 SQL files
- [ ] Run setup.bat
- [ ] Create master templates for all loan schemes
- [ ] Upload templates to scheme folders
- [ ] Test with sample profiles
- [ ] Train users on new workflow
- [ ] Set up backup system
- [ ] Configure email notifications (optional)
- [ ] Set proper file permissions on generated/ folder
- [ ] Test with multiple concurrent users

---

## 🚨 Troubleshooting

**"Composer not found"**
- Download from: https://getcomposer.org/
- Install globally
- Restart command prompt

**"PHPWord not found"**
- Run: `composer require phpoffice/phpword` in DAS folder
- Check vendor/autoload.php exists

**"Table profile_documents doesn't exist"**
- Import workflow_enhancements.sql
- Or manually import master_architecture_migration.sql

**"Can't delete template"**
- Run master_architecture_migration.sql first
- This removes template_id from loan_details

**"Rows not cloning in document"**
- Check template has only ONE row with placeholders
- Verify placeholder names match exactly
- Ensure table is properly formatted in Word

---

## 🎉 You're Ready!

The complete DAS system is now implemented with:

✅ Document generation from master templates
✅ Dynamic handling of unlimited co-borrowers/guarantors
✅ Profile workflow (submit → pick → approve → generate)
✅ Borrower/Guarantor reuse system
✅ Comments and review system
✅ Template management
✅ Comprehensive placeholder library

**Next:** Run setup.bat and import the SQL files to activate everything!
