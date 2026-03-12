import React, { useState, useEffect } from 'react';
import api from '../api';
import { Shield, TrendingUp, Award, UserCheck } from 'lucide-react';

const AdminDesignations = () => {
    const [scores, setScores] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchScores();
    }, []);

    const fetchScores = async () => {
        try {
            const res = await api.get('/leads/scores');
            setScores(res.data);
            setLoading(false);
        } catch (err) {
            console.error('Error fetching scores');
            setLoading(false);
        }
    };

    return (
        <div className="container" style={{ padding: '20px' }}>
            <header style={{ marginBottom: '30px' }}>
                <h1 style={{ fontSize: '2rem', fontWeight: '800', margin: 0, display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <Shield size={32} className="text-primary" /> Admin Command Center
                </h1>
                <p style={{ color: 'var(--text-muted)' }}>Enterprise Authority Management & Staff Reliability Scoring</p>
            </header>

            <div className="form-row">
                <div className="glass-card" style={{ flex: 2 }}>
                    <h3 style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <TrendingUp size={20} className="text-accent" /> Staff Performance Leaderboard
                    </h3>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                            <thead>
                                <tr style={{ borderBottom: '2px solid var(--secondary)', color: 'var(--text-muted)', textAlign: 'left' }}>
                                    <th style={{ padding: '15px' }}>Staff Name</th>
                                    <th style={{ padding: '15px' }}>Designation</th>
                                    <th style={{ padding: '15px' }}>Decisions</th>
                                    <th style={{ padding: '15px' }}>Confidence Score</th>
                                    <th style={{ padding: '15px', textAlign: 'right' }}>Total Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                {scores.map((s, idx) => (
                                    <tr key={idx} style={{ borderBottom: '1px solid var(--secondary)' }}>
                                        <td style={{ padding: '15px', fontWeight: '600' }}>{s.name}</td>
                                        <td style={{ padding: '15px' }}>{s.designation}</td>
                                        <td style={{ padding: '15px' }}>{s.total_actions}</td>
                                        <td style={{ padding: '15px' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                                <div style={{ flex: 1, height: '6px', background: '#e2e8f0', borderRadius: '3px', overflow: 'hidden' }}>
                                                    <div style={{ width: `${s.confidence_score}%`, height: '100%', background: s.confidence_score > 70 ? 'var(--accent)' : '#f59e0b' }}></div>
                                                </div>
                                                <span style={{ fontSize: '0.8rem', fontWeight: '700' }}>{s.confidence_score}%</span>
                                            </div>
                                        </td>
                                        <td style={{ padding: '15px', textAlign: 'right' }}>
                                            <span style={{ 
                                                padding: '5px 15px', borderRadius: '15px', fontWeight: '800', 
                                                background: s.total_points >= 0 ? '#dcfce7' : '#fee2e2',
                                                color: s.total_points >= 0 ? '#166534' : '#b91c1c'
                                            }}>
                                                {s.total_points} Pts
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: '20px' }}>
                    <div className="glass-card" style={{ background: 'linear-gradient(135deg, var(--primary), #1e40af)', color: 'white' }}>
                        <Award size={40} style={{ marginBottom: '15px', opacity: 0.8 }} />
                        <h3>Top Performer</h3>
                        {scores[0] ? (
                            <div style={{ marginTop: '10px' }}>
                                <div style={{ fontSize: '1.2rem', fontWeight: '700' }}>{scores[0].name}</div>
                                <div style={{ fontSize: '0.9rem', opacity: 0.9 }}>{scores[0].designation}</div>
                                <div style={{ fontSize: '1.5rem', fontWeight: '900', marginTop: '10px' }}>{scores[0].total_points} <span style={{ fontSize: '0.8rem' }}>POINTS</span></div>
                            </div>
                        ) : 'Calculating...'}
                    </div>

                    <div className="glass-card">
                        <UserCheck size={24} className="text-primary" style={{ marginBottom: '10px' }} />
                        <h4>Authority Status</h4>
                        <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginTop: '5px' }}>
                            All Branch Managers are restricted to <b>25 Lakh</b> limits until manually overridden.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AdminDesignations;
