import React, { useState, useEffect } from 'react';
import api from '../api';
import { Search, GitMerge } from 'lucide-react';

const WorkflowsPage = () => {
    const [workflows, setWorkflows] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        fetchWorkflows();
    }, []);

    const fetchWorkflows = async () => {
        try {
            const res = await api.get('/workflows');
            setWorkflows(res.data);
        } catch (err) {
            console.error('Error fetching workflows:', err);
        }
    };

    const filteredWorkflows = workflows.filter(wf => 
        wf.customer_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        wf.cap_id.toLowerCase().includes(searchTerm.toLowerCase()) ||
        wf.lead_id.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div style={{ padding: '10px' }}>
            <div className="glass-card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '25px', flexWrap: 'wrap', gap: '15px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <GitMerge className="text-primary" />
                        <h3 style={{ margin: 0 }}>Active Workflows</h3>
                    </div>
                    <div style={{ position: 'relative', width: '300px' }}>
                        <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input 
                            type="text" 
                            placeholder="Search by CAP ID, Lead ID or Customer..." 
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            style={{ 
                                width: '100%', 
                                padding: '10px 10px 10px 40px', 
                                background: 'white', 
                                border: '1px solid #e2e8f0', 
                                borderRadius: '8px',
                                outline: 'none'
                            }}
                        />
                    </div>
                </div>

                <div style={{ overflowX: 'auto' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr style={{ borderBottom: '1px solid var(--glass-border)', textAlign: 'left' }}>
                                <th style={{ padding: '12px' }}>CAP ID</th>
                                <th style={{ padding: '12px' }}>Customer Name</th>
                                <th style={{ padding: '12px' }}>Current Step</th>
                                <th style={{ padding: '12px' }}>Assigned To</th>
                                <th style={{ padding: '12px' }}>Status</th>
                                <th style={{ padding: '12px' }}>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredWorkflows.length > 0 ? filteredWorkflows.map(wf => (
                                <tr key={wf.id} style={{ borderBottom: '1px solid var(--glass-border)', transition: 'background 0.2s' }}>
                                    <td style={{ padding: '12px', fontWeight: '600', color: 'var(--primary)' }}>{wf.cap_id}</td>
                                    <td style={{ padding: '12px' }}>{wf.customer_name}</td>
                                    <td style={{ padding: '12px' }}>
                                        <div style={{ display: 'flex', flexDirection: 'column' }}>
                                            <span style={{ fontWeight: '500' }}>{wf.current_step}</span>
                                            <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{wf.file_type}</span>
                                        </div>
                                    </td>
                                    <td style={{ padding: '12px' }}>{wf.assigned_role}</td>
                                    <td style={{ padding: '12px' }}>
                                        <span style={{ 
                                            padding: '4px 10px', 
                                            borderRadius: '20px', 
                                            background: wf.file_status === 'Pending' ? '#fff7ed' : '#f0fdf4', 
                                            color: wf.file_status === 'Pending' ? '#9a3412' : '#166534',
                                            fontSize: '0.75rem',
                                            fontWeight: '600'
                                        }}>
                                            {wf.file_status}
                                        </span>
                                    </td>
                                    <td style={{ padding: '12px', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                                        {new Date(wf.updated_at).toLocaleDateString()}
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="6" style={{ textAlign: 'center', padding: '30px', color: 'var(--text-muted)' }}>No workflows found matching your search.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default WorkflowsPage;
