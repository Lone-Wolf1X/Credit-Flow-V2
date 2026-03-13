import React, { useState, useRef, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { Menu, X, LogOut, User, Award, ChevronRight, LayoutDashboard, GitMerge, FileText, Settings, Users, Home, Shield, TrendingUp, Search, Calculator } from 'lucide-react';
import NotificationBell from './NotificationBell';
import { Link, useLocation, useNavigate } from 'react-router-dom';

const Layout = ({ children }) => {
    const [isSidebarOpen, setSidebarOpen] = useState(true);
    const { user, logout } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();
    const [isProfileOpen, setProfileOpen] = useState(false);
    const profileRef = useRef(null);

    const toggleSidebar = () => setSidebarOpen(!isSidebarOpen);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (profileRef.current && !profileRef.current.contains(event.target)) {
                setProfileOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const menuItems = [
        { name: 'Dashboard', path: '/', icon: <LayoutDashboard size={20} /> },
        { name: 'Leads', path: '/leads', icon: <Home size={20} /> },
        { name: 'Appraisals', path: '/appraisals', icon: <Calculator size={20} /> },
        { name: 'Workflows', path: '/workflows', icon: <GitMerge size={20} /> },
        { name: 'Memos', path: '/memos', icon: <FileText size={20} /> }
    ];

    if (user?.role === 'Admin') {
        menuItems.push(
            { name: 'User Management', path: '/admin/users', icon: <Users size={20} /> },
            { name: 'Branch Management', path: '/admin/branches', icon: <Settings size={20} /> },
            { name: 'Staff Performance', path: '/admin/scores', icon: <Award size={20} /> },
            { name: 'Security Logs', path: '/admin/security', icon: <Shield size={20} /> },
            { name: 'Authority Matrix', path: '/admin/matrix', icon: <TrendingUp size={20} /> }
        );
    }

    return (
        <div style={{ display: 'flex', minHeight: '100vh', background: 'var(--bg-main)' }}>
            {/* Sidebar */}
            <div className={`glass-card`} style={{
                width: isSidebarOpen ? '280px' : '88px',
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                background: 'rgba(255, 255, 255, 0.9)',
                backdropFilter: 'blur(20px)',
                margin: '0',
                borderRadius: '0',
                display: 'flex',
                flexDirection: 'column',
                padding: '30px 15px',
                position: 'fixed',
                height: '100vh',
                zIndex: 1000,
                borderRight: '1px solid rgba(0, 0, 0, 0.05)',
                boxShadow: '10px 0 30px rgba(0,0,0,0.02)'
            }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: isSidebarOpen ? 'space-between' : 'center', marginBottom: '30px', padding: '0 10px' }}>
                    {isSidebarOpen && <h3 style={{ fontSize: '1.2rem', color: 'var(--primary)', fontWeight: 'bold' }}>Credit Flow</h3>}
                    <button onClick={toggleSidebar} style={{ background: 'none', border: 'none', color: 'var(--text-main)', cursor: 'pointer' }}>
                        {isSidebarOpen ? <X size={24} /> : <Menu size={24} />}
                    </button>
                </div>

                <div style={{ flex: 1 }}>
                    {/* ID Quick Search */}
                    {isSidebarOpen && (
                        <div style={{ padding: '0 10px 20px 10px' }}>
                            <div style={{ position: 'relative' }}>
                                <Search size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                                <input 
                                    type="text" placeholder="Jump to ID..." 
                                    style={{ 
                                        width: '100%', padding: '10px 10px 10px 35px', borderRadius: '10px', 
                                        border: '1px solid var(--glass-border)', fontSize: '0.8rem',
                                        background: 'rgba(255,255,255,0.5)'
                                    }}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && e.target.value) {
                                            navigate(`/leads/${e.target.value.trim()}`);
                                            e.target.value = '';
                                        }
                                    }}
                                />
                            </div>
                        </div>
                    )}

                    {menuItems.map((item) => (
                        <Link key={item.name} to={item.path} style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: isSidebarOpen ? 'flex-start' : 'center',
                            padding: '12px 15px',
                            marginBottom: '8px',
                            borderRadius: '10px',
                            textDecoration: 'none',
                            color: location.pathname === item.path ? 'white' : 'var(--text-muted)',
                            background: location.pathname === item.path ? 'var(--primary)' : 'transparent',
                            transition: 'all 0.2s'
                        }}>
                            {item.icon}
                            {isSidebarOpen && <span style={{ marginLeft: '12px', fontWeight: '500' }}>{item.name}</span>}
                        </Link>
                    ))}
                </div>

                <div style={{ borderTop: '1px solid var(--glass-border)', paddingTop: '10px' }}>
                    <button onClick={logout} style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: isSidebarOpen ? 'flex-start' : 'center',
                        width: '100%',
                        padding: '12px 15px',
                        marginBottom: '10px',
                        borderRadius: '10px',
                        border: 'none',
                        background: 'rgba(239, 68, 68, 0.1)',
                        color: 'var(--danger)',
                        cursor: 'pointer',
                        transition: 'all 0.2s',
                        fontWeight: '600'
                    }}>
                        <LogOut size={20} />
                        {isSidebarOpen && <span style={{ marginLeft: '12px' }}>Logout</span>}
                    </button>
                    <p style={{ fontSize: '0.7rem', color: 'var(--text-muted)', textAlign: 'center' }}>
                        {isSidebarOpen ? 'Next Gen Innovation' : 'NGI'}
                    </p>
                </div>
            </div>

            {/* Main Content */}
            <div style={{ 
                flex: 1, 
                marginLeft: isSidebarOpen ? '280px' : '88px', 
                transition: 'margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                padding: '0 20px 20px 20px',
                display: 'flex',
                flexDirection: 'column'
            }}>
                {/* Navbar */}
                <div className="glass-card" style={{ 
                    display: 'flex', 
                    justifyContent: 'space-between', 
                    alignItems: 'center', 
                    padding: '12px 30px',
                    marginBottom: '24px',
                    borderRadius: '0',
                    border: 'none',
                    borderBottom: '1px solid rgba(0, 0, 0, 0.05)',
                    background: 'rgba(255, 255, 255, 0.8)',
                    backdropFilter: 'blur(10px)',
                    position: 'sticky',
                    top: '0',
                    zIndex: 900,
                    margin: '0 -20px 24px -20px', // Pull to edges
                    boxShadow: 'none'
                }}>
                    <h2 style={{ fontSize: '1.1rem' }}>{menuItems.find(i => i.path === location.pathname)?.name || 'Dashboard'}</h2>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '25px' }}>
                        <NotificationBell />
                        <div style={{ position: 'relative' }} ref={profileRef}>
                            <div 
                                onClick={() => setProfileOpen(!isProfileOpen)}
                                style={{ display: 'flex', alignItems: 'center', gap: '12px', cursor: 'pointer' }}
                            >
                                <div style={{ textAlign: 'right' }}>
                                    <p style={{ fontSize: '0.9rem', fontWeight: '600', margin: 0 }}>{user?.name}</p>
                                    <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', margin: 0 }}>{user?.role}</p>
                                </div>
                                <div style={{ 
                                    width: '40px', 
                                    height: '40px', 
                                    borderRadius: '50%', 
                                    background: 'var(--primary)', 
                                    display: 'flex', 
                                    justifyContent: 'center', 
                                    alignItems: 'center',
                                    border: '2px solid var(--primary-light)',
                                    fontSize: '1.1rem',
                                    fontWeight: 'bold',
                                    color: 'white'
                                }}>
                                    {user?.name?.charAt(0)}
                                </div>
                            </div>
                            
                            {isProfileOpen && (
                                <div className="glass-card" style={{
                                    position: 'absolute',
                                    top: '70px',
                                    right: '20px',
                                    width: '250px',
                                    padding: '10px',
                                    boxShadow: '0 15px 30px rgba(0,0,0,0.2)',
                                    zIndex: 9999
                                }}>
                                    <Link to="/profile" onClick={() => setProfileOpen(false)} style={{ 
                                        display: 'flex', 
                                        alignItems: 'center', 
                                        width: '100%', 
                                        padding: '10px',
                                        fontSize: '0.9rem',
                                        color: 'var(--text-main)',
                                        textDecoration: 'none',
                                        marginBottom: '5px',
                                        borderRadius: '8px',
                                        transition: 'background 0.2s'
                                    }}
                                    onMouseOver={(e) => e.target.style.background = 'var(--secondary)'}
                                    onMouseOut={(e) => e.target.style.background = 'transparent'}
                                    >
                                        <User size={18} style={{ marginRight: '10px' }} />
                                        My Profile
                                    </Link>
                                    <button onClick={logout} className="btn" style={{ 
                                        display: 'flex', 
                                        alignItems: 'center', 
                                        width: '100%', 
                                        background: 'rgba(239, 68, 68, 0.1)', 
                                        color: 'var(--danger)',
                                        padding: '10px',
                                        fontSize: '0.9rem'
                                    }}>
                                        <LogOut size={18} style={{ marginRight: '10px' }} />
                                        Logout
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div style={{ flex: 1 }}>
                    {children}
                </div>

                {/* Footer */}
                <footer style={{ 
                    marginTop: '40px', 
                    padding: '20px', 
                    textAlign: 'center', 
                    color: 'var(--text-muted)',
                    fontSize: '0.85rem'
                }}>
                    <p>© 2026 Next Gen Innovation Nepal Private Limited. All rights reserved.</p>
                </footer>
            </div>
        </div>
    );
};

export default Layout;
