// Nodemailer removed as per user request
require('dotenv').config();

exports.sendEmail = async (to, subject, title, message, leadDetails = null) => {
    // Email functionality disabled - Logging to console instead
    console.log(`[EMAIL-STUB] To: ${to} | Subject: ${subject}`);
    console.log(`[EMAIL-MSG] Title: ${title} | Body: ${message}`);
};

exports.sendPasswordResetEmail = async (to, tempPassword) => {
    const subject = 'Temporary Password - Credit Flow System';
    const title = 'Password Reset Initiated by Admin';
    const message = `An admin has reset your password. Your temporary password is: <b>${tempPassword}</b><br><br>Please login and change your password immediately.`;
    
    await exports.sendEmail(to, subject, title, message);
};
