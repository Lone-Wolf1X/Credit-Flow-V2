# Template Management Workflow - Garage vs Scheme Folders

## 🎯 Recommended Approach: Two-Folder System

Your instinct is **correct**! Separating garage and scheme folders is the **best practice**.

---

## 📁 Folder Structure

```
DAS/
├── templates/
│   ├── garage/                    ← EDITING AREA (Admin only)
│   │   ├── draft_loan_agreement.docx
│   │   ├── draft_sanction_letter.docx
│   │   └── work_in_progress.docx
│   │
│   ├── personal_loan/             ← PRODUCTION (Active use)
│   │   ├── loan_agreement.docx
│   │   └── sanction_letter.docx
│   │
│   ├── home_loan/                 ← PRODUCTION
│   │   ├── loan_agreement.docx
│   │   └── mortgage_deed.docx
│   │
│   └── vehicle_loan/              ← PRODUCTION
│       └── loan_agreement.docx
```

---

## 🔄 Complete Workflow

### **Phase 1: Template Creation (In Garage)**

```
1. Admin → DAS Template Garage
2. Click "Create New Template"
3. Upload blank DOCX to garage folder
4. Status: "Draft"
```

### **Phase 2: Adding Placeholders (In Garage)**

```
1. Admin → Edit template in garage
2. View placeholder library (150+ available)
3. Copy placeholders
4. Download template
5. Open in Microsoft Word
6. Paste placeholders in appropriate positions
7. Format nicely
8. Save
```

**Example:**
```docx
BEFORE:
Borrower Name: ________________

AFTER (with placeholders):
Borrower Name: ${borrower_name}
Citizenship: ${borrower_cit}
Address: ${borrower_add}
```

### **Phase 3: Testing (In Garage)**

```
1. Upload modified template back to garage
2. Click "Test Generate" (optional feature)
3. Verify placeholders work
4. Fix any issues
5. Iterate until perfect
```

### **Phase 4: Publishing (Move to Scheme Folder)**

```
1. Admin → Template Garage
2. Select template from garage
3. Click "Publish to Scheme"
4. Choose loan scheme (personal_loan, home_loan, etc.)
5. Template copied to scheme folder
6. Status: "Active"
7. Now available for document generation
```

---

## 💾 Database Schema for This Workflow

### **Update templates Table:**

```sql
ALTER TABLE templates 
ADD COLUMN template_status ENUM('Draft', 'Testing', 'Active', 'Archived') DEFAULT 'Draft',
ADD COLUMN is_in_garage TINYINT(1) DEFAULT 1 COMMENT '1=Garage, 0=Published to scheme',
ADD COLUMN garage_file_path VARCHAR(500) NULL COMMENT 'Path in garage folder',
ADD COLUMN published_at TIMESTAMP NULL COMMENT 'When published to scheme folder';
```

### **Template Lifecycle:**

```
Draft (garage) → Testing (garage) → Active (scheme) → Archived (storage)
```

---

## ✅ Benefits of This Approach

| Aspect | Without Separation | With Garage + Scheme |
|--------|-------------------|---------------------|
| **Safety** | Edit production files directly | Edit in isolated garage |
| **Testing** | Users see broken templates | Test before publishing |
| **Versioning** | Overwrite originals | Keep versions |
| **Organization** | Mixed draft/prod | Clear separation |
| **Rollback** | Difficult | Easy - just unpublish |

---

## 🛠️ Implementation: Enhanced Template Garage

### **New Features to Add:**

#### **1. Template Status Indicator**

```php
<!-- In das_template_garage.php -->
<span class="badge bg-<?php echo getStatusColor($template['template_status']); ?>">
    <?php echo $template['template_status']; ?>
</span>
```

#### **2. Publish Button**

```php
<?php if ($template['is_in_garage']): ?>
    <button class="btn btn-success" onclick="publishTemplate(<?php echo $template['id']; ?>)">
        <i class="bi bi-upload"></i> Publish to Scheme
    </button>
<?php else: ?>
    <button class="btn btn-warning" onclick="unpublishTemplate(<?php echo $template['id']; ?>)">
        <i class="bi bi-arrow-left"></i> Move Back to Garage
    </button>
<?php endif; ?>
```

#### **3. Placeholder Helper Panel**

```php
<!-- Add sidebar to template editing page -->
<div class="placeholder-helper">
    <h5>Available Placeholders</h5>
    <input type="text" placeholder="Search placeholders..." onkeyup="filterPlaceholders(this.value)">
    
    <div id="placeholderList">
        <?php
        $placeholders = $das_conn->query("SELECT * FROM template_placeholders ORDER BY category, placeholder_name");
        $current_category = '';
        
        while ($ph = $placeholders->fetch_assoc()) {
            if ($ph['category'] != $current_category) {
                echo "<h6>{$ph['category']}</h6>";
                $current_category = $ph['category'];
            }
            echo "<div class='placeholder-item' onclick='copyPlaceholder(\"{$ph['placeholder_key']}\")'>";
            echo "<code>\${{{$ph['placeholder_key']}}}</code>";
            echo "<span class='text-muted'>{$ph['placeholder_name']}</span>";
            echo "</div>";
        }
        ?>
    </div>
</div>

<script>
function copyPlaceholder(key) {
    const placeholder = '${' + key + '}';
    navigator.clipboard.writeText(placeholder);
    alert('Copied: ' + placeholder);
}
</script>
```

---

## 📝 Recommended User Workflow

### **For Admin (Template Creator):**

**Step 1: Create in Garage**
```
1. Go to: Admin → DAS Template Garage
2. Click "Upload New Template"
3. Upload to "Garage" folder
4. Status automatically set to "Draft"
```

**Step 2: Edit with Placeholders**
```
1. In Template Garage, click "View Placeholders"
2. Browse available placeholders by category:
   - Customer Profile
   - Borrower
   - Guarantor
   - Loan Details
   - Collateral
   - System
3. Click to copy placeholder
4. Download template
5. Open in Microsoft Word
6. Paste placeholders where needed
7. Format document beautifully
8. Save
```

**Step 3: Re-upload and Test**
```
1. Upload modified template (replaces garage version)
2. Click "Test with Sample Data" (you can build this feature)
3. Verify output looks correct
4. Fix any issues
5. Repeat until perfect
```

**Step 4: Publish**
```
1. Click "Publish to Scheme"
2. Select target scheme (Personal Loan, etc.)
3. Template copied to scheme folder
4. Status changes to "Active"
5. Now available for document generation
```

**Step 5: Maintain**
```
- Edit published template → Creates new garage draft
- Edit in garage → Test → Publish update
- Old version archived automatically
```

---

## 🎨 UI Enhancement for Template Garage

```html
<!-- Enhanced Template Card -->
<div class="template-card">
    <div class="card-header">
        <div class="d-flex justify-content-between">
            <h5>Loan Agreement</h5>
            <div>
                <span class="badge bg-warning">Draft</span>
                <span class="badge bg-secondary">In Garage</span>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <p class="text-muted">Personal Loan template with all placeholders</p>
        
        <div class="btn-group">
            <button class="btn btn-primary">
                <i class="bi bi-download"></i> Download
            </button>
            <button class="btn btn-info">
                <i class="bi bi-eye"></i> View Placeholders
            </button>
            <button class="btn btn-success">
                <i class="bi bi-upload"></i> Publish to Scheme
            </button>
        </div>
    </div>
    
    <div class="card-footer text-muted">
        Created: 2025-12-20 | Version: 1.0
    </div>
</div>
```

---

## ⚡ Quick Migration Script

If you want to implement this now:

```sql
-- Add new columns to templates table
ALTER TABLE templates 
ADD COLUMN template_status ENUM('Draft', 'Testing', 'Active', 'Archived') DEFAULT 'Draft',
ADD COLUMN is_in_garage TINYINT(1) DEFAULT 0,
ADD COLUMN garage_file_path VARCHAR(500) NULL,
ADD COLUMN production_file_path VARCHAR(500) NULL COMMENT 'Path in scheme folder',
ADD COLUMN published_at TIMESTAMP NULL;

-- Update existing templates to show they're in production
UPDATE templates 
SET template_status = 'Active', 
    is_in_garage = 0,
    production_file_path = file_path;
```

---

## 🎯 Summary Recommendation

**BEST APPROACH:**

✅ **Garage Folder**: For editing, testing, adding placeholders
✅ **Scheme Folders**: For production use only
✅ **Clear Status**: Draft → Testing → Active → Archived
✅ **One-Click Publish**: Copy from garage to scheme
✅ **Placeholder Helper**: Panel showing all 150+ placeholders
✅ **Version Control**: Keep history of changes

**BENEFITS:**
- ✅ Safe editing (don't break production)
- ✅ Easy testing
- ✅ Clear workflow
- ✅ Can rollback
- ✅ Multiple versions possible

**NEXT STEPS:**
1. Run `verify_database.sql` to check current state
2. Create `garage/` folder in `DAS/templates/`
3. Enhance Template Garage UI with publish button
4. Add placeholder helper panel
5. Update workflow documentation

Would you like me to build the enhanced Template Garage page with these features?
