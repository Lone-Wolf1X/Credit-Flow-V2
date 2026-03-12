# DAS Documents Folder

This folder contains all generated documents organized by customer profile.

## Structure
```
documents/
├── profile_1/
│   ├── original_20251225_001234.docx
│   └── renewal_20251226_001235.docx
├── profile_2/
│   └── original_20251225_001236.docx
└── .gitkeep
```

## Naming Convention
`{document_type}_{timestamp}_{unique_id}.{extension}`

- **document_type**: original, renewal, enhancement, reduction
- **timestamp**: YYYYMMDDHHmmss
- **unique_id**: Unique identifier
- **extension**: docx, pdf, etc.

## Notes
- Each customer profile has its own folder
- Documents are independent of templates
- Folder is created automatically on first document generation
