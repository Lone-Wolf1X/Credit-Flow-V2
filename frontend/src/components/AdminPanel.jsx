import React, { useState, useEffect } from 'react';
import api from '../api';

const AdminPanel = () => {
    const [allLeads, setAllLeads] = useState([]);
    const [allWorkflows, setAllWorkflows] = useState([]);

    useEffect(() => {
        fetchAll();
    }, []);

    const fetchAll = async () => {
        try {
            const leads = await api.get('/leads');
            const workflows = await api.get('/workflows');
            setAllLeads(leads.data);
            setAllWorkflows(workflows.data);
        } catch (err) {}
    };

    const downloadAuditLogs = async () => {
        window.open('http://localhost:5000/api/audit/export', '_blank');
    };

    return (
        <div className="glass-card" style={{ marginTop: '30px' }}>
            <h3>Admin Monitoring Panel</h3>
            <button className="btn btn-primary" onClick={downloadAuditLogs} style={{ marginTop: '10px', marginBottom: '20px' }}>
                Export Audit Logs (PDF)
            </button>
            
            <div className="dashboard-grid">
                <div className="glass-card">
                    <h4>Total System Leads</h4>
                    <p style={{ fontSize: '1.5rem' }}>{allLeads.length}</p>
                </div>
                <div className="glass-card">
                    <h4>Total Workflows</h4>
                    <p style={{ fontSize: '1.5rem' }}>{allWorkflows.length}</p>
                </div>
            </div>
        </div>
    );
};

export default AdminPanel;
