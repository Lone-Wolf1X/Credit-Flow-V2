import React, { useState, useEffect } from 'react';
import { Bell } from 'lucide-react';
import { useSocket } from '../context/SocketContext';
import api from '../api';

const NotificationBell = () => {
    const [notifications, setNotifications] = useState([]);
    const [show, setShow] = useState(false);
    const socket = useSocket();

    useEffect(() => {
        fetchNotifications();
        if (socket) {
            socket.on('notification', (notif) => {
                setNotifications(prev => [notif, ...prev]);
            });
        }
    }, [socket]);

    const fetchNotifications = async () => {
        try {
            const res = await api.get('/notifications');
            setNotifications(res.data);
        } catch (err) {}
    };

    return (
        <div style={{ position: 'relative' }}>
            <Bell onClick={() => setShow(!show)} style={{ cursor: 'pointer' }} />
            {notifications.filter(n => !n.is_read).length > 0 && (
                <span style={{ position: 'absolute', top: -5, right: -5, background: 'red', borderRadius: '50%', padding: '2px 6px', fontSize: '10px' }}>
                    {notifications.filter(n => !n.is_read).length}
                </span>
            )}
            {show && (
                <div className="glass-card" style={{ position: 'absolute', right: 0, top: 40, width: 300, zIndex: 100, maxHeight: 400, overflowY: 'auto' }}>
                    <h4>Notifications</h4>
                    {notifications.length === 0 ? <p>No notifications</p> : notifications.map((n, i) => (
                        <div key={i} style={{ borderBottom: '1px solid var(--glass-border)', padding: '10px 0' }}>
                            <p style={{ fontSize: '0.85rem' }}>{n.message}</p>
                            <small>{new Date(n.created_at).toLocaleString()}</small>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default NotificationBell;
