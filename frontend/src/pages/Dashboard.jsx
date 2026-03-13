import React, { useState, useEffect } from 'react';
import api from '../api';
import { useAuth } from '../context/AuthContext';
import AdminPanel from '../components/AdminPanel';
import { 
    Users, 
    GitMerge, 
    CheckCircle, 
    TrendingUp, 
    Clock, 
    ShieldCheck, 
    ArrowUpRight,
    Search
} from 'lucide-react';

const Dashboard = () => {
    const [stats, setStats] = useState({
        totalLeads: 0,
        activeWorkflows: 0,
        conversions: 0,
        conversionRate: 0,
        pendingAnalysis: 0
    });
    const { user } = useAuth();

    useEffect(() => {
        fetchStats();
    }, []);

    const fetchStats = async () => {
        try {
            const [leadsRes, workflowsRes] = await Promise.all([
                api.get('/leads'),
                api.get('/workflows')
            ]);
            
            const leads = leadsRes.data;
            const workflows = workflowsRes.data;
            
            // Role-based filtering
            let filteredLeads = leads;
            let filteredWorkflows = workflows;
            
            if (user?.role !== 'Admin') {
                if (user?.designation === 'Branch Manager') {
                    filteredLeads = leads.filter(l => l.branch_id === user.branch_id);
                    filteredWorkflows = workflows.filter(w => w.current_handler_id === user.id);
                } else if (user?.designation === 'Province Head') {
                    // Assuming leads have province_id or we filter by branch's province
                    filteredWorkflows = workflows.filter(w => w.current_handler_id === user.id);
                }
            }

            const total = filteredLeads.length;
            const active = filteredWorkflows.length;
            const converted = filteredLeads.filter(l => l.status === 'Converted').length;
            const pendingAn = filteredLeads.filter(l => l.status === 'Analysis' || l.status === 'Ongoing').length;
            const rate = total > 0 ? ((converted / total) * 100).toFixed(1) : 0;

            setStats({
                totalLeads: total,
                activeWorkflows: active,
                conversions: converted,
                conversionRate: rate,
                pendingAnalysis: pendingAn
            });
        } catch (err) {
            console.error('Error fetching stats:', err);
        }
    };

    const KpiCard = ({ icon: Icon, label, value, trend, color, bg }) => (
        <div className="glass-card" style={{ 
            position: 'relative', 
            overflow: 'hidden',
            transition: 'transform 0.3s ease'
        }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                <div style={{ 
                    padding: '12px', 
                    background: bg, 
                    borderRadius: '14px', 
                    color: color,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                }}>
                    <Icon size={24} />
                </div>
                {trend && (
                    <div style={{ 
                        fontSize: '0.8rem', 
                        fontWeight: '600', 
                        color: trend.startsWith('+') ? '#10b981' : '#ef4444',
                        background: trend.startsWith('+') ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)',
                        padding: '4px 10px',
                        borderRadius: '20px',
                        height: 'fit-content',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px'
                    }}>
                        {trend} <ArrowUpRight size={14} />
                    </div>
                )}
            </div>
            <div>
                <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '8px', fontWeight: '500' }}>{label}</p>
                <div style={{ display: 'flex', alignItems: 'baseline', gap: '8px' }}>
                    <h2 style={{ fontSize: '2rem', margin: 0, fontWeight: '700', color: 'var(--text-main)' }}>{value}</h2>
                </div>
            </div>
            <div style={{ 
                position: 'absolute', 
                bottom: '-20px', 
                right: '-20px', 
                opacity: 0.05, 
                color: color 
            }}>
                <Icon size={100} />
            </div>
        </div>
    );

    const getRoleTitle = () => {
        if (user?.role === 'Admin') return 'Central Administration';
        if (user?.designation === 'Branch Manager') return `Branch Command: ${user.branch_name}`;
        if (user?.designation === 'Province Head') return `Provincial Oversight: ${user.province_name}`;
        return 'Staff Operations';
    };

    return (
        <div style={{ padding: '24px', width: '100%' }}>
            <div style={{ marginBottom: '32px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                    <h1 style={{ fontSize: '2rem', fontWeight: '800', margin: '0 0 8px 0', color: 'var(--text-main)' }}>
                        {getRoleTitle()}
                    </h1>
                    <p style={{ color: 'var(--text-muted)', margin: 0 }}>
                        Welcome back, <span style={{ color: 'var(--primary)', fontWeight: '600' }}>{user?.name}</span>. {user?.role === 'Admin' ? "System status is stable." : "Here is your current queue overview."}
                    </p>
                </div>
                <div style={{ display: 'flex', gap: '12px' }}>
                    <div className="glass-card" style={{ padding: '8px 16px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <Clock size={18} color="var(--text-muted)" />
                        <span style={{ fontSize: '0.9rem', fontWeight: '600' }}>{new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric' })}</span>
                    </div>
                </div>
            </div>

            <div className="dashboard-grid" style={{ marginBottom: '32px' }}>
                <KpiCard 
                    icon={Users} 
                    label={user?.role === 'Admin' ? "System Wide Leads" : "Branch/Region Leads"} 
                    value={stats.totalLeads} 
                    trend="+12%" 
                    color="var(--primary)" 
                    bg="rgba(37, 99, 235, 0.15)"
                />
                <KpiCard 
                    icon={GitMerge} 
                    label="My Pending Reviews" 
                    value={stats.activeWorkflows} 
                    trend="+5%" 
                    color="#8b5cf6" 
                    bg="rgba(139, 92, 246, 0.15)"
                />
                <KpiCard 
                    icon={ShieldCheck} 
                    label="Portfolio Conversions" 
                    value={stats.conversions} 
                    trend="+8%" 
                    color="var(--accent)" 
                    bg="rgba(16, 185, 129, 0.15)"
                />
                <KpiCard 
                    icon={TrendingUp} 
                    label="Efficiency Yield" 
                    value={`${stats.conversionRate}%`} 
                    trend="+2.4%" 
                    color="var(--warning)" 
                    bg="rgba(245, 158, 11, 0.15)"
                />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: user?.role === 'Admin' ? '1fr' : 'repeat(auto-fit, minmax(400px, 1fr))', gap: '32px' }}>
                {user?.role === 'Admin' ? (
                    <div className="glass-card" style={{ padding: '0' }}>
                        <div style={{ padding: '24px', borderBottom: '1px solid var(--glass-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontWeight: '700' }}>Administrative Control Center</h3>
                            <button className="btn btn-primary" style={{ padding: '8px 16px', fontSize: '0.85rem' }}>View Full Audit Logs</button>
                        </div>
                        <AdminPanel />
                    </div>
                ) : (
                    <>
                        <div className="glass-card">
                            <h3 style={{ marginBottom: '24px', fontWeight: '700' }}>Recent Notifications</h3>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                                <p style={{ color: 'var(--text-muted)', textAlign: 'center', padding: '40px 0' }}>No recent alerts.</p>
                            </div>
                        </div>
                        <div className="glass-card">
                            <h3 style={{ marginBottom: '24px', fontWeight: '700' }}>Quick Actions</h3>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
                                <button className="btn" style={{ background: 'white', border: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '8px', padding: '24px' }}>
                                    <Search size={24} color="var(--primary)" />
                                    <span style={{ fontSize: '0.9rem' }}>Search Lead</span>
                                </button>
                                <button className="btn" style={{ background: 'white', border: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '8px', padding: '24px' }}>
                                    <ArrowUpRight size={24} color="var(--accent)" />
                                    <span style={{ fontSize: '0.9rem' }}>Initiate Memo</span>
                                </button>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
};

export default Dashboard;
