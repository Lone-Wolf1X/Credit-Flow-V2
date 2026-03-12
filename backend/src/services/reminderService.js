const db = require('../db');

/**
 * Service to handle automated reminders for stagnant leads.
 */
const checkStagnantLeads = async (io) => {
    try {
        console.log('Running Stagnant Lead Check...');

        // 1. Check Drafts > 7 days old
        const stagnantDrafts = await db.query(`
            SELECT l.*, u.id as user_id, u.email, u.name as user_name 
            FROM leads l 
            JOIN users u ON l.initiator_id = u.id 
            WHERE l.status NOT IN ('Converted', 'Rejected') 
            AND l.updated_at < NOW() - INTERVAL '48 hours'
        `);

        for (const lead of stagnantDrafts.rows) {
            const message = `Lead ${lead.lead_id} (Draft) is over a week old. Please process or dispose.`;
            await db.query('INSERT INTO notifications (user_id, message, type) VALUES ($1, $2, $3)', 
                [lead.user_id, message, 'Reminder']);
            
            if (io) io.to(`user_${lead.user_id}`).emit('notification', { message });
        }

        // 2. Check Analysis/Ongoing > 3 days old with no action
        const stagnantReviews = await db.query(`
            SELECT l.id, l.lead_id, l.customer_name, l.current_owner_id, u.name as handler_name
            FROM leads l
            JOIN users u ON l.current_owner_id = u.id
            WHERE l.status IN ('Analysis', 'Ongoing')
            AND l.updated_at < CURRENT_TIMESTAMP - INTERVAL '3 days'
        `);

        for (const lead of stagnantReviews.rows) {
            const message = `ACTION REQUIRED: Lead ${lead.lead_id} has been pending with you for over 3 days.`;
            await db.query('INSERT INTO notifications (user_id, message, type) VALUES ($1, $2, $3)', 
                [lead.current_owner_id, message, 'Urgent']);
            
            if (io) io.to(`user_${lead.current_owner_id}`).emit('notification', { message });
        }

    } catch (err) {
        console.error('Reminder Service Error:', err);
    }
};

// Run every 24 hours (or simulation for demo)
const startReminderService = (io) => {
    // Initial check
    checkStagnantLeads(io);
    
    // Interval: 24 hours
    setInterval(() => checkStagnantLeads(io), 24 * 60 * 60 * 1000);
};

module.exports = { startReminderService };
