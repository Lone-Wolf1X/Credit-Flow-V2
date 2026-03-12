import React, { useState, useEffect } from 'react';
import api from '../api';
import { Shield, Save, X, Edit2, TrendingUp } from 'lucide-react';

const AuthorityMatrix = () => {
    const [designations, setDesignations] = useState([]);
    const [editingId, setEditingId] = useState(null);
    const [editValue, setEditValue] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDesignations();
    }, []);

    const fetchDesignations = async () => {
        try {
            const res = await api.get('/users/designations');
            setDesignations(res.data);
            setLoading(false);
        } catch (err) {
            console.error('Error fetching designations');
            setLoading(false);
        }
    };

    const handleEdit = (d) => {
        setEditingId(d.id);
        setEditValue(d.default_power_limit);
    };

    const handleSave = async (id) => {
        try {
            await api.put(`/users/designations/${id}`, { default_power_limit: editValue });
            setEditingId(null);
            fetchDesignations();
            alert('Authority limit updated!');
        } catch (err) {
            alert('Update failed');
        }
    };

    const formatCurrency = (val) => {
        return new Intl.NumberFormat('en-NP', {
            style: 'currency',
            currency: 'NPR',
            minimumFractionDigits: 0
        }).format(val).replace('NPR', 'रु');
    };

    if (loading) return <div>Loading Matrix...</div>;

    return (
        <div className="container" style={{ padding: '20px' }}>
            <header style={{ marginBottom: '30px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                    <h1 style={{ fontSize: '2rem', fontWeight: '800', margin: 0, display: 'flex', alignItems: 'center', gap: '15px' }}>
                        <Shield size={32} className="text-primary" /> Authority Matrix
                    </h1>
                    <p style={{ color: 'var(--text-muted)' }}>Configure lending authority limits for all roles</p>
                </div>
            </header>

            <div className="glass-card">
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--secondary)', textAlign: 'left', color: 'var(--text-muted)' }}>
                            <th style={{ padding: '15px' }}>Designation</th>
                            <th style={{ padding: '15px' }}>Current Limit (NPR)</th>
                            <th style={{ padding: '15px', textAlign: 'right' }}>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {designations.map(d => (
                            <tr key={d.id} style={{ borderBottom: '1px solid var(--secondary)' }}>
                                <td style={{ padding: '15px', fontWeight: '600', fontSize: '1.1rem' }}>
                                    {d.name}
                                </td>
                                <td style={{ padding: '15px' }}>
                                    {editingId === d.id ? (
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                            <span>रु</span>
                                            <input 
                                                type="number" 
                                                value={editValue} 
                                                onChange={(e) => setEditValue(e.target.value)}
                                                style={{ width: '180px', padding: '8px' }}
                                                autoFocus
                                            />
                                        </div>
                                    ) : (
                                        <span style={{ 
                                            padding: '5px 15px', borderRadius: '15px', fontWeight: '700',
                                            background: '#dcfce7', color: '#166534', fontSize: '1.1rem'
                                        }}>
                                            {formatCurrency(d.default_power_limit)}
                                        </span>
                                    )}
                                </td>
                                <td style={{ padding: '15px', textAlign: 'right' }}>
                                    {editingId === d.id ? (
                                        <>
                                            <button onClick={() => handleSave(d.id)} className="btn btn-primary" style={{ padding: '8px 15px', marginRight: '10px' }}>
                                                <Save size={18} />
                                            </button>
                                            <button onClick={() => setEditingId(null)} className="btn" style={{ padding: '8px 15px', background: 'var(--danger)', color: 'white' }}>
                                                <X size={18} />
                                            </button>
                                        </>
                                    ) : (
                                        <button onClick={() => handleEdit(d)} className="btn" style={{ background: 'var(--secondary)', color: 'var(--primary)' }}>
                                            <Edit2 size={18} /> Edit Limit
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div style={{ marginTop: '30px' }} className="glass-card">
                <h4 style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '15px' }}>
                    <TrendingUp size={20} className="text-accent" /> System Logic
                </h4>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>
                    These limits define the maximum amount a user can process or approve. 
                    If a lead exceeds these limits, the system will automatically require escalation to the next authority level.
                </p>
            </div>
        </div>
    );
};

export default AuthorityMatrix;
