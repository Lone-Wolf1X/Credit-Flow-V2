import React, { useState, useEffect } from 'react';
import api from '../api';
import { Shield, Save, X, Edit2, TrendingUp } from 'lucide-react';

const AuthorityMatrix = () => {
    const [designations, setDesignations] = useState([]);
    const [usersWithOverrides, setUsersWithOverrides] = useState([]);
    const [editingId, setEditingId] = useState(null);
    const [editValue, setEditValue] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [desigRes, userRes] = await Promise.all([
                api.get('/users/designations'),
                api.get('/users')
            ]);
            setDesignations(desigRes.data);
            setUsersWithOverrides(userRes.data.filter(u => parseFloat(u.limit_power) > 0));
            setLoading(false);
        } catch (err) {
            console.error('Error fetching matrix data');
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
            fetchData();
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
                    <p style={{ color: 'var(--text-muted)' }}>Configure lending authority limits for all roles and individual staff overrides</p>
                </div>
            </header>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 400px', gap: '24px' }}>
                {/* Standard Designation Limits */}
                <div className="glass-card" style={{ padding: '0', overflow: 'hidden' }}>
                    <div style={{ padding: '20px', background: 'var(--primary)', color: 'white' }}>
                        <h3 style={{ margin: 0, fontSize: '1.2rem', fontWeight: '800' }}>Designation Defaults</h3>
                    </div>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr style={{ borderBottom: '2px solid var(--secondary)', textAlign: 'left', color: 'var(--text-muted)' }}>
                                <th style={{ padding: '15px' }}>Designation</th>
                                <th style={{ padding: '15px' }}>Base Limit (NPR)</th>
                                <th style={{ padding: '15px', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {designations.map(d => (
                                <tr key={d.id} style={{ borderBottom: '1px solid var(--secondary)' }}>
                                    <td style={{ padding: '15px', fontWeight: '600' }}>{d.name}</td>
                                    <td style={{ padding: '15px' }}>
                                        {editingId === d.id ? (
                                            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <span>रु</span>
                                                <input 
                                                    type="number" value={editValue} 
                                                    onChange={(e) => setEditValue(e.target.value)}
                                                    style={{ width: '140px', padding: '8px', border: '2px solid var(--primary)', borderRadius: '8px' }}
                                                    autoFocus
                                                />
                                            </div>
                                        ) : (
                                            <span style={{ fontWeight: '800', color: 'var(--primary)' }}>{formatCurrency(d.default_power_limit)}</span>
                                        )}
                                    </td>
                                    <td style={{ padding: '15px', textAlign: 'right' }}>
                                        {editingId === d.id ? (
                                            <button onClick={() => handleSave(d.id)} className="btn btn-primary" style={{ padding: '8px 15px' }}>Save</button>
                                        ) : (
                                            <button onClick={() => handleEdit(d)} className="btn" style={{ background: 'var(--secondary)', color: 'var(--primary)', fontSize: '0.8rem' }}>Edit</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Individual Overrides */}
                <div className="glass-card" style={{ padding: '0', overflow: 'hidden' }}>
                    <div style={{ padding: '20px', background: 'var(--accent)', color: 'white' }}>
                        <h3 style={{ margin: 0, fontSize: '1.2rem', fontWeight: '800' }}>Custom User Overrides</h3>
                    </div>
                    <div style={{ padding: '20px' }}>
                        {usersWithOverrides.length === 0 ? (
                            <p style={{ color: 'var(--text-muted)', textAlign: 'center', margin: '20px 0' }}>No specific overrides assigned.</p>
                        ) : (
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                {usersWithOverrides.map(u => (
                                    <div key={u.id} style={{ padding: '12px', background: '#f8fafc', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
                                        <div style={{ fontWeight: '700', fontSize: '0.9rem' }}>{u.name}</div>
                                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{u.designation} | {u.branch_name}</div>
                                        <div style={{ marginTop: '8px', fontWeight: '800', color: 'var(--accent)', fontSize: '1rem' }}>
                                            {formatCurrency(u.limit_power)}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                        <p style={{ marginTop: '20px', fontSize: '0.75rem', color: 'var(--text-muted)', fontStyle: 'italic' }}>
                            * User-specific limits given in User Management take precedence over designation defaults.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AuthorityMatrix;
