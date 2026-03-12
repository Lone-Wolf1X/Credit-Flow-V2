import React, { useState, useEffect } from 'react';
import api from '../api';
import { Shield, Clock, FileText, Download, Filter, Search } from 'lucide-react';

const SecurityLogs = () => {
    const [auditLogs, setAuditLogs] = useState([]);
    const [sessionLogs, setSessionLogs] = useState([]);
    const [activeTab, setActiveTab] = useState('audit'); // 'audit' or 'session'
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        fetchLogs();
    }, []);

    const fetchLogs = async () => {
        setLoading(true);
        try {
            const [auditRes, sessionRes] = await Promise.all([
                api.get('/audit'),
                api.get('/audit/sessions') // Need to add this route
            ]);
            setAuditLogs(auditRes.data);
            setSessionLogs(sessionRes.data);
        } catch (err) {
            console.error('Error fetching logs:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleExport = async () => {
        window.open(`${api.defaults.baseURL}/audit/export`, '_blank');
    };

    const filteredAudit = auditLogs.filter(log => 
        log.user_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        log.action.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const filteredSessions = sessionLogs.filter(log => 
        log.user_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        log.action.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div className="container">
            <div className="glass-card" style={{ padding: '30px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <Shield size={28} className="text-primary" />
                        <h3 style={{ margin: 0 }}>Banking Security & Audit Logs</h3>
                    </div>
                    <div style={{ display: 'flex', gap: '10px' }}>
                        <button onClick={handleExport} className="btn" style={{ display: 'flex', alignItems: 'center', gap: '8px', background: 'var(--secondary)', color: 'var(--primary)' }}>
                            <Download size={18} /> Export PDF
                        </button>
                        <button onClick={fetchLogs} className="btn btn-primary" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <Clock size={18} /> Refresh
                        </button>
                    </div>
                </div>

                <div style={{ display: 'flex', gap: '20px', marginBottom: '25px', borderBottom: '1px solid var(--glass-border)' }}>
                    <button 
                        onClick={() => setActiveTab('audit')}
                        style={{ 
                            padding: '10px 20px', border: 'none', background: 'none', 
                            borderBottom: activeTab === 'audit' ? '3px solid var(--primary)' : '3px solid transparent',
                            color: activeTab === 'audit' ? 'var(--primary)' : 'var(--text-muted)',
                            fontWeight: '700', cursor: 'pointer'
                        }}
                    >
                        Audit Trails (Actions)
                    </button>
                    <button 
                        onClick={() => setActiveTab('session')}
                        style={{ 
                            padding: '10px 20px', border: 'none', background: 'none', 
                            borderBottom: activeTab === 'session' ? '3px solid var(--primary)' : '3px solid transparent',
                            color: activeTab === 'session' ? 'var(--primary)' : 'var(--text-muted)',
                            fontWeight: '700', cursor: 'pointer'
                        }}
                    >
                        Session Logs (Login/Logout)
                    </button>
                </div>

                <div style={{ position: 'relative', marginBottom: '20px', maxWidth: '400px' }}>
                    <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input 
                        type="text" 
                        placeholder="Search by user or action..." 
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        style={{ paddingLeft: '40px' }}
                    />
                </div>

                {loading ? (
                    <div style={{ padding: '50px', textAlign: 'center' }}>Loading logs...</div>
                ) : (
                    <div style={{ overflowX: 'auto' }}>
                        {activeTab === 'audit' ? (
                            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                <thead>
                                    <tr style={{ borderBottom: '1px solid var(--glass-border)', textAlign: 'left' }}>
                                        <th style={{ padding: '12px' }}>User</th>
                                        <th style={{ padding: '12px' }}>Action</th>
                                        <th style={{ padding: '12px' }}>Details</th>
                                        <th style={{ padding: '12px' }}>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredAudit.map(log => (
                                        <tr key={log.id} style={{ borderBottom: '1px solid var(--glass-border)', fontSize: '0.9rem' }}>
                                            <td style={{ padding: '12px', fontWeight: '600' }}>{log.user_name}</td>
                                            <td style={{ padding: '12px' }}><code style={{ background: '#f1f5f9', padding: '2px 6px', borderRadius: '4px' }}>{log.action}</code></td>
                                            <td style={{ padding: '12px' }}>
                                                <div style={{ maxWidth: '300px', fontSize: '0.75rem', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                                    {JSON.stringify(log.details)}
                                                </div>
                                            </td>
                                            <td style={{ padding: '12px', color: 'var(--text-muted)' }}>{new Date(log.created_at).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                <thead>
                                    <tr style={{ borderBottom: '1px solid var(--glass-border)', textAlign: 'left' }}>
                                        <th style={{ padding: '12px' }}>User</th>
                                        <th style={{ padding: '12px' }}>Event</th>
                                        <th style={{ padding: '12px' }}>IP Address</th>
                                        <th style={{ padding: '12px' }}>Device/Browser</th>
                                        <th style={{ padding: '12px' }}>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredSessions.map(log => (
                                        <tr key={log.id} style={{ borderBottom: '1px solid var(--glass-border)', fontSize: '0.9rem' }}>
                                            <td style={{ padding: '12px', fontWeight: '600' }}>{log.user_name}</td>
                                            <td style={{ padding: '12px' }}>
                                                <span style={{ 
                                                    padding: '3px 10px', borderRadius: '20px', fontSize: '0.7rem', fontWeight: '800',
                                                    background: log.action === 'LOGIN' ? '#f0fdf4' : '#fef2f2',
                                                    color: log.action === 'LOGIN' ? '#166534' : '#991b1b'
                                                }}>
                                                    {log.action}
                                                </span>
                                            </td>
                                            <td style={{ padding: '12px' }}>{log.ip_address}</td>
                                            <td style={{ padding: '12px', maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{log.user_agent}</td>
                                            <td style={{ padding: '12px', color: 'var(--text-muted)' }}>{new Date(log.created_at).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

export default SecurityLogs;
