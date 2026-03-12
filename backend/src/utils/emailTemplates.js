const getEmailTemplate = (title, message, leadDetails = null) => {
    return `
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f9; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: #ffffff; padding: 30px; text-align: center; }
            .content { padding: 30px; color: #333333; line-height: 1.6; }
            .details { background-color: #f9fafb; padding: 20px; border-radius: 6px; margin-top: 20px; border: 1px solid #e5e7eb; }
            .footer { background-color: #f3f4f6; color: #6b7280; padding: 20px; text-align: center; font-size: 12px; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>${title}</h1>
            </div>
            <div class="content">
                <p>${message}</p>
                ${leadDetails ? `
                    <div class="details">
                        <p><strong>Lead ID:</strong> ${leadDetails.lead_id}</p>
                        <p><strong>Applicant:</strong> ${leadDetails.customer_name}</p>
                        <p><strong>Proposed Limit:</strong> ${leadDetails.proposed_limit}</p>
                        <p><strong>Status:</strong> ${leadDetails.status}</p>
                    </div>
                ` : ''}
                <a href="${process.env.FRONTEND_URL}" class="btn">View in Dashboard</a>
            </div>
            <div class="footer">
                <p>© 2026 Next Gen Innovation Nepal Private Limited. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    `;
};

module.exports = getEmailTemplate;
