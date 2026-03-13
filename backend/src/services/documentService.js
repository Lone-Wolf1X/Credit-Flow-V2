const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType, AlignmentType, HeadingLevel } = require('docx');
const fs = require('fs');

/**
 * Generates a formal Mortgage Term Loan Appraisal DOCX
 */
exports.generateAppraisalDocx = async (data) => {
    const doc = new Document({
        sections: [{
            properties: {},
            children: [
                new Paragraph({
                    text: "MORTGAGE TERM LOAN - APPRAISAL REPORT",
                    heading: HeadingLevel.HEADING_1,
                    alignment: AlignmentType.CENTER,
                }),
                new Paragraph({
                    children: [
                        new TextRun({ text: `Customer Name: ${data.customer_name}`, bold: true }),
                        new TextRun({ text: `\nLead ID: ${data.lead_identifier}`, break: 1 }),
                        new TextRun({ text: `\nDate: ${new Date().toLocaleDateString()}`, break: 1 }),
                    ],
                }),

                new Paragraph({ text: "1. BORROWER DETAILS", heading: HeadingLevel.HEADING_2, spacing: { before: 400 } }),
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("Age")] }),
                                new TableCell({ children: [new Paragraph(data.borrower_details.age || "N/A")] }),
                            ],
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("PAN No.")] }),
                                new TableCell({ children: [new Paragraph(data.borrower_details.pan || "N/A")] }),
                            ],
                        }),
                    ],
                }),

                new Paragraph({ text: "2. INCOME ASSESSMENT", heading: HeadingLevel.HEADING_2, spacing: { before: 400 } }),
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("Agriculture Net (75%)")] }),
                                new TableCell({ children: [new Paragraph(data.income_details.agriculture_net?.toLocaleString() || "0")] }),
                            ],
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("Remittance Net (100%)")] }),
                                new TableCell({ children: [new Paragraph(data.income_details.remittance_net?.toLocaleString() || "0")] }),
                            ],
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("Salary Net (After TDS)")] }),
                                new TableCell({ children: [new Paragraph(data.income_details.salary_net?.toLocaleString() || "0")] }),
                            ],
                        }),
                    ],
                }),

                new Paragraph({ text: "3. DEBT TO INCOME (DTI)", heading: HeadingLevel.HEADING_2, spacing: { before: 400 } }),
                new Paragraph({
                    children: [
                        new TextRun({ text: `DTI Ratio: ${data.income_details.dti_ratio?.toFixed(2)}%`, bold: true }),
                        new TextRun({ text: `\nNet Uncommitted Income: रु ${data.income_details.total_uncommitted_income?.toLocaleString()}`, break: 1 }),
                    ],
                }),

                new Paragraph({ text: "4. COLLATERAL & VALUATION", heading: HeadingLevel.HEADING_2, spacing: { before: 400 } }),
                new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("FMV")] }),
                                new TableCell({ children: [new Paragraph(data.valuations.fmv?.toLocaleString() || "0")] }),
                            ],
                        }),
                        new TableRow({
                            children: [
                                new TableCell({ children: [new Paragraph("DV")] }),
                                new TableCell({ children: [new Paragraph(data.valuations.dv?.toLocaleString() || "0")] }),
                            ],
                        }),
                    ],
                }),

                new Paragraph({ text: "5. FINAL RECOMMENDATION", heading: HeadingLevel.HEADING_2, spacing: { before: 400 } }),
                new Paragraph({ text: data.final_recommendation.justification || "No justification provided." }),
            ],
        }],
    });

    return await Packer.toBuffer(doc);
};
