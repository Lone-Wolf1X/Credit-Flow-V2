const { logAction } = require('../controllers/auditController');

const auditMiddleware = (req, res, next) => {
    // We only care about mutations (POST, PUT, DELETE)
    if (['POST', 'PUT', 'DELETE'].includes(req.method)) {
        const originalSend = res.send;
        
        res.send = function(data) {
            // Check if request was successful (2xx)
            if (res.statusCode >= 200 && res.statusCode < 300) {
                const user_id = req.user ? req.user.id : null;
                const action = `${req.method} ${req.originalUrl}`;
                const details = {
                    body: req.body,
                    params: req.params,
                    query: req.query,
                    ip: req.ip
                };
                
                // Exclude sensitive data from logs (like passwords)
                if (req.body && details.body.password) details.body.password = '********';
                if (req.body && details.body.newPassword) details.body.newPassword = '********';

                logAction(user_id, action, details).catch(err => console.error('Delayed audit log error:', err));
            }
            return originalSend.apply(res, arguments);
        };
    }
    next();
};

module.exports = auditMiddleware;
