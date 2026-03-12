# Debugging Guide - Document Generation Issues

## 🔍 **Tools Created**

### 1. Placeholder Debugger
**File:** `C:\xampp\htdocs\Credit\DAS\debug_placeholders.php`

**How to Use:**
1. Open in browser: `http://localhost/Credit/DAS/debug_placeholders.php?profile_id=2`
2. Change `profile_id=2` to your actual profile ID
3. This will show you:
   - All placeholders being generated
   - Their mapped values
   - Empty placeholders (highlighted in red)
   - Sample document snippet
   - Copy-paste list of all placeholders

### 2. UI Console Logging
**File:** `customer_profile.php` (updated)

**How to Use:**
1. Open customer profile page in browser
2. Press F12 to open Developer Tools
3. Go to Console tab
4. Refresh page
5. Look for:
   - `Loading documents for profile: X`
   - `Documents API response: {...}`
   - `Found X documents`

---

## 🛠️ **Fixing Your Issues**

### Issue 1: Download Buttons Not Showing

**Possible Causes:**
1. Documents not saved to database correctly
2. API not returning documents
3. JavaScript error preventing display

**Debug Steps:**
1. **Check Database:**
   ```sql
   SELECT * FROM generated_documents WHERE customer_profile_id = 2;
   ```
   - Look at `file_path` column - it should be like: `generated_documents/2025002_Name/Template_123.docx`

2. **Check API Response:**
   - Open: `http://localhost/Credit/DAS/modules/api/customer_api.php?action=get_generated_documents&profile_id=2`
   - Should return JSON with documents array
   - Check `file_path` values

3. **Check Console:**
   - Open profile page, press F12
   - Look for errors in Console tab
   - Should see "Found X documents"

**If Downloads Still Don't Show:**
- Documents section only shows when `status = 'Approved'`
- Check profile status in database

### Issue 2: Placeholders Not Being Replaced

**Common Reasons:**
1. **Wrong Placeholder Format**
   - Must be: `${BR1_NM_NP}` (with curly braces)
   - NOT: `$BR1_NM_NP` or `{BR1_NM_NP}`

2. **Missing Data**
   - Use debug tool to see which placeholders are empty
   - Fill in missing data in profile

3. **Typo in Template**
   - Check template placeholders match exactly
   - Case-sensitive: `${BR1_FATHER}` not `${br1_father}`

4. **Data Not Fetched**
   - Check rule file: `DAS/includes/rules/mortgage_deed.json`
   - Ensure data sources are configured

**Debug Steps:**
1. Run placeholder debugger:
   ```
   http://localhost/Credit/DAS/debug_placeholders.php?profile_id=YOUR_ID
   ```

2. Check "Empty Placeholders" section
   - Red highlighted = needs data

3. Compare with your template:
   - Open your Word template
   - Search for `${` 
   - Check if placeholder exists in debug tool

4. **Common Missing Placeholders:**
   - Family members (father, mother, etc.)
   - Collateral owner details
   - Bank information

---

## 📋 **Testing Checklist**

Before approving a profile:

- [ ] Add at least 1 borrower with ALL fields filled
- [ ] Add at least 1 collateral with owner selected
- [ ] Add loan details (amount, scheme, etc.)
- [ ] Run debug tool to check all placeholders have values
- [ ] Check template uses correct placeholder format `${}`
- [ ] Verify profile status shows "Submitted"

After approving:

- [ ] Check "Generated Documents" section appears
- [ ] See download button(s)
- [ ] Click download - file downloads successfully
- [ ] Open Word document
- [ ] Check placeholders are replaced
- [ ] Verify text is bold
- [ ] Verify font size preserved

---

## 🎯 **Quick Fixes**

### Fix 1: Reset a Profile for Re-testing
```sql
-- Change profile back to Submitted status
UPDATE customer_profiles SET status = 'Submitted' WHERE id = 2;

-- Delete old documents
DELETE FROM generated_documents WHERE customer_profile_id = 2;
```

### Fix 2: Check What's in Generated Folder
```
Navigate to: C:\xampp\htdocs\Credit\DAS\generated_documents\
Look for: 2025002_BorrowerName\
Files should be: TemplateName_Timestamp.docx
```

### Fix 3: Test Download Link Manually
```
Format: http://localhost/Credit/DAS/download_document.php?file=FOLDER_NAME/FILENAME.docx
Example: http://localhost/Credit/DAS/download_document.php?file=2025002_Ram_Thapa/Mortgage_Deed_20260109001234.docx
```

---

## 📞 **Getting Help**

If you still have issues, provide:

1. **Screenshot** of debug tool output
2. **Console errors** (from F12 Developer Tools)
3. **Template file** (or screenshot of placeholders used)
4. **Profile ID** you're testing with
5. **Database query result**:
   ```sql
   SELECT * FROM generated_documents WHERE customer_profile_id = YOUR_ID;
   ```

This will help identify the exact problem!

---

## 🔑 **Key Files Reference**

- **Placeholder Mapping:** `PlaceholderLibrary.php`
- **Document Generator:** `DocumentGenerator.php`
- **Rule Engine:** `DocumentRuleEngine.php`
- **Rules Config:** `includes/rules/mortgage_deed.json`
- **Download Script:** `download_document.php`
- **UI Display:** `modules/customer/customer_profile.php`
- **API Endpoint:** `modules/api/customer_api.php`
