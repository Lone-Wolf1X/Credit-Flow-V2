# Comprehensive Placeholder Reference Guide

## Quick Reference: All Available Placeholders

### Customer Profile
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{customer.id}}` | Customer ID | 2025001 |
| `{{customer.type}}` | Individual/Corporate | Individual |
| `{{customer.full_name}}` | Full name | Rajesh Kumar Sharma |
| `{{customer.email}}` | Email address | rajesh@example.com |
| `{{customer.contact}}` | Contact number | 9841234567 |
| `{{customer.province}}` | Province | Bagmati |
| `{{customer.sol}}` | Service Outlet Location | KTM-001 |

### Borrower - Basic Info
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{borrower.full_name}}` | Full name | Rajesh Kumar Sharma |
| `{{borrower.type}}` | Individual/Corporate | Individual |
| `{{borrower.dob}}` | Date of birth | 1990-01-15 |
| `{{borrower.dob_nepali}}` | DOB in Nepali | 2046-10-01 |
| `{{borrower.age}}` | Age in years | 35 |
| `{{borrower.gender}}` | Gender | Male |
| `{{borrower.relationship_status}}` | Marital status | Married |
| `{{borrower.father_name}}` | Father's name | Krishna Prasad Sharma |

### Borrower - ID Documents
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{borrower.citizenship_number}}` | Citizenship no. | 12-01-75-12345 |
| `{{borrower.id_issue_date}}` | Issue date | 2010-05-15 |
| `{{borrower.id_issue_district}}` | Issue district | Kathmandu |
| `{{borrower.id_issue_authority}}` | Issuing authority | DAO Kathmandu |
| `{{borrower.id_reissue_date}}` | Reissue date | 2020-06-10 |
| `{{borrower.reissue_count}}` | Reissue count | First |

### Borrower - Corporate
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{borrower.company_name}}` | Company name | ABC Pvt. Ltd. |
| `{{borrower.registration_no}}` | Registration no. | 123456/078/079 |
| `{{borrower.registration_date}}` | Registration date | 2020-01-15 |
| `{{borrower.registration_type}}` | Type | Private Limited |
| `{{borrower.pan_number}}` | PAN number | 123456789 |
| `{{borrower.pan_issue_date}}` | PAN issue date | 2020-02-01 |

### Borrower - Permanent Address
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{borrower.perm_country}}` | Country | Nepal |
| `{{borrower.perm_province}}` | Province | Bagmati |
| `{{borrower.perm_district}}` | District | Kathmandu |
| `{{borrower.perm_municipality}}` | Municipality | Kathmandu Metro |
| `{{borrower.perm_ward}}` | Ward number | 10 |
| `{{borrower.perm_town}}` | Town/Village | Baneshwor |
| `{{borrower.perm_street_name}}` | Street name | Madan Bhandari Path |
| `{{borrower.perm_street_number}}` | House number | 123 |
| `{{borrower.perm_address_full}}` | Full address | Baneshwor, Ward 10, Kathmandu |

### Borrower - Temporary Address
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{borrower.temp_province}}` | Province | Bagmati |
| `{{borrower.temp_district}}` | District | Lalitpur |
| `{{borrower.temp_municipality}}` | Municipality | Lalitpur Sub-Metro |
| `{{borrower.temp_ward}}` | Ward number | 5 |
| `{{borrower.temp_town}}` | Town/Village | Jawalakhel |
| `{{borrower.temp_address_full}}` | Full address | Jawalakhel, Ward 5, Lalitpur |

### Guarantor - Basic Info
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{guarantor.full_name}}` | Full name | Sita Sharma |
| `{{guarantor.type}}` | Individual/Corporate | Individual |
| `{{guarantor.dob}}` | Date of birth | 1985-05-20 |
| `{{guarantor.age}}` | Age | 40 |
| `{{guarantor.gender}}` | Gender | Female |
| `{{guarantor.citizenship_number}}` | Citizenship no. | 12-01-75-67890 |
| `{{guarantor.father_name}}` | Father's name | Ram Prasad Sharma |

### Guarantor - Address
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{guarantor.perm_province}}` | Permanent province | Bagmati |
| `{{guarantor.perm_district}}` | Permanent district | Bhaktapur |
| `{{guarantor.perm_ward}}` | Permanent ward | 8 |
| `{{guarantor.perm_address_full}}` | Full permanent address | Ward 8, Bhaktapur |
| `{{guarantor.temp_address_full}}` | Full temporary address | Ward 15, Kathmandu |

### Collateral - Land
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{collateral.type}}` | Type | Land |
| `{{collateral.owner_name}}` | Owner name | Rajesh Kumar Sharma |
| `{{collateral.land_province}}` | Province | Bagmati |
| `{{collateral.land_district}}` | District | Kathmandu |
| `{{collateral.land_ward}}` | Ward no. | 10 |
| `{{collateral.land_sheet_no}}` | Sheet number | 123 |
| `{{collateral.land_kitta_no}}` | Kitta number | 456 |
| `{{collateral.land_area}}` | Area | 0-5-2-0 (5 Aana 2 Paisa) |
| `{{collateral.land_malpot_office}}` | Malpot office | Kathmandu Malpot |
| `{{collateral.land_location_full}}` | Full location | Sheet 123, Kitta 456, Ward 10 |

### Collateral - Vehicle
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{collateral.vehicle_model}}` | Model | Toyota Corolla 2020 |
| `{{collateral.vehicle_engine_no}}` | Engine number | ENG123456789 |
| `{{collateral.vehicle_chassis_no}}` | Chassis number | CHS987654321 |
| `{{collateral.vehicle_no}}` | Vehicle number | BA 1 KHA 1234 |

### Loan Details
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{loan.type}}` | Loan type | Personal Term Loan |
| `{{loan.scheme}}` | Loan scheme | Regular Scheme |
| `{{loan.amount}}` | Loan amount | 500000.00 |
| `{{loan.amount_words}}` | Amount in words | Five Lakh Only |
| `{{loan.amount_words_nepali}}` | Amount (Nepali) | पाँच लाख मात्र |
| `{{loan.tenure}}` | Tenure (months) | 60 |
| `{{loan.tenure_years}}` | Tenure (years) | 5 |
| `{{loan.interest_rate}}` | Interest rate % | 12.50 |
| `{{loan.base_rate}}` | Base rate % | 10.00 |
| `{{loan.premium}}` | Premium % | 2.50 |
| `{{loan.approved_date}}` | Approval date | 2025-12-20 |
| `{{loan.approved_date_nepali}}` | Approval date (BS) | 2081-09-06 |
| `{{loan.emi_amount}}` | Monthly EMI | 11122.22 |
| `{{loan.emi_amount_words}}` | EMI in words | Eleven Thousand... |
| `{{loan.total_interest}}` | Total interest | 167333.20 |
| `{{loan.total_repayment}}` | Total repayment | 667333.20 |

### System & Dates
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{date.today}}` | Today's date (AD) | 2025-12-20 |
| `{{date.today_nepali}}` | Today's date (BS) | 2081-09-06 |
| `{{date.year}}` | Current year | 2025 |
| `{{date.month}}` | Current month | December |
| `{{date.day}}` | Current day | 20 |

### Bank Information
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{bank.name}}` | Bank name | State Bank of India |
| `{{bank.address}}` | Bank address | Kathmandu, Nepal |
| `{{bank.branch}}` | Branch name | Kathmandu Branch |
| `{{bank.branch_code}}` | Branch code | KTM-001 |
| `{{bank.contact}}` | Contact number | 01-4123456 |
| `{{bank.email}}` | Email | info@bank.com.np |

### Document Info
| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{document.reference_no}}` | Reference number | DOC-2025-001 |
| `{{document.generated_date}}` | Generated date | 2025-12-20 |
| `{{document.generated_by}}` | Generated by | Maker Name |

## Usage Examples

### Simple Loan Agreement
```
LOAN AGREEMENT

Date: {{date.today}} ({{date.today_nepali}})

This agreement is made between:

BORROWER:
Name: {{borrower.full_name}}
Father's Name: {{borrower.father_name}}
Citizenship No: {{borrower.citizenship_number}}
Permanent Address: {{borrower.perm_address_full}}

LOAN DETAILS:
Amount: Rs. {{loan.amount}} ({{loan.amount_words}})
Interest Rate: {{loan.interest_rate}}% per annum
Tenure: {{loan.tenure_years}} years ({{loan.tenure}} months)
Monthly EMI: Rs. {{loan.emi_amount}}
```

### Collateral Details Table
```
COLLATERAL DETAILS

Land Information:
Location: {{collateral.land_location_full}}
Sheet No: {{collateral.land_sheet_no}}
Kitta No: {{collateral.land_kitta_no}}
Area: {{collateral.land_area}}
Malpot Office: {{collateral.land_malpot_office}}
Owner: {{collateral.owner_name}}
```

### Guarantor Section
```
GUARANTOR INFORMATION

Name: {{guarantor.full_name}}
Father's Name: {{guarantor.father_name}}
Citizenship: {{guarantor.citizenship_number}}
Address: {{guarantor.perm_address_full}}
Contact: {{customer.contact}}
```

## Tips for Using Placeholders

1. **Copy-Paste**: Click placeholder in sidebar to copy
2. **Consistent Format**: Always use `{{category.field}}`
3. **Check Spelling**: Placeholders are case-sensitive
4. **Preview Data**: Use placeholder library to see example values
5. **Test First**: Generate test document before production use

## Total Count: 150+ Placeholders

Categories:
- Customer Profile: 9
- Borrower: 50+
- Guarantor: 25+
- Collateral: 20+
- Loan: 25+
- System: 15+
