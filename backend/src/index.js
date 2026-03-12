const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const morgan = require('morgan');
require('dotenv').config();

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: process.env.FRONTEND_URL || "http://localhost:5173",
        methods: ["GET", "POST"]
    }
});

// Middleware
app.use(cors());
app.use(morgan('dev'));
app.use(express.json());
app.use(require('./middleware/auditMiddleware'));

// Socket.IO
io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);
    
    socket.on('join', (userId) => {
        socket.join(`user_${userId}`);
        console.log(`User ${userId} joined their room`);
    });

    socket.on('disconnect', () => {
        console.log('User disconnected');
    });
});

// Export io to use in routes/controllers
app.set('io', io);

// Routes
app.use('/api/auth', require('./routes/authRoutes'));
app.use('/api/branches', require('./routes/branchRoutes'));
app.use('/api/leads', require('./routes/leadRoutes'));
app.use('/api/workflows', require('./routes/workflowRoutes'));
app.use('/api/memos', require('./routes/memoRoutes'));
app.use('/api/audit', require('./routes/auditRoutes'));
app.use('/api/notifications', require('./routes/notificationRoutes'));
app.use('/api/users', require('./routes/userRoutes'));
app.use('/api/appraisals', require('./routes/appraisalRoutes'));

// Static folder for uploads
app.use('/uploads', express.static('uploads'));

// Basic Route
app.get('/', (req, res) => {
    res.json({ message: "Banking Credit Appraisal API is running..." });
});

// Start Server
const PORT = process.env.PORT || 5000;
server.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
    // Start automated reminders
    require('./services/reminderService').startReminderService(io);
});
