import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { SocketProvider } from './context/SocketContext';
import Dashboard from './pages/Dashboard';
import LeadsPage from './pages/LeadsPage';
import WorkflowsPage from './pages/WorkflowsPage';
import MemosPage from './pages/MemosPage';
import LoginPage from './pages/LoginPage';
import PasswordReset from './pages/PasswordReset';
import UserManagement from './pages/UserManagement';
import BranchManagement from './pages/BranchManagement';
import AdminDesignations from './pages/AdminDesignations';
import UserProfile from './pages/UserProfile';
import SecurityLogs from './pages/SecurityLogs';
import AuthorityMatrix from './pages/AuthorityMatrix';
import LeadDetailsPage from './pages/LeadDetailsPage';
import Layout from './components/Layout';

const ProtectedRoute = ({ children }) => {
    const { user, loading } = useAuth();
    if (loading) return <div>Loading...</div>;
    if (!user) return <Navigate to="/login" />;
    if (user.must_reset_password) return <PasswordReset />;
    return <Layout>{children}</Layout>;
};

import AppraisalPage from './pages/AppraisalPage';

function App() {
    return (
        <AuthProvider>
            <SocketProvider>
                <Router>
                    <Routes>
                        <Route path="/login" element={<LoginPage />} />
                        <Route path="/" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />
                        <Route path="/leads" element={<ProtectedRoute><LeadsPage /></ProtectedRoute>} />
                        <Route path="/workflows" element={<ProtectedRoute><WorkflowsPage /></ProtectedRoute>} />
                        <Route path="/memos" element={<ProtectedRoute><MemosPage /></ProtectedRoute>} />
                        <Route path="/admin/users" element={<ProtectedRoute><UserManagement /></ProtectedRoute>} />
                        <Route path="/admin/branches" element={<ProtectedRoute><BranchManagement /></ProtectedRoute>} />
                        <Route path="/admin/scores" element={<ProtectedRoute><AdminDesignations /></ProtectedRoute>} />
                        <Route path="/admin/security" element={<ProtectedRoute><SecurityLogs /></ProtectedRoute>} />
                        <Route path="/admin/matrix" element={<ProtectedRoute><AuthorityMatrix /></ProtectedRoute>} />
                        <Route path="/leads/:id" element={<ProtectedRoute><LeadDetailsPage /></ProtectedRoute>} />
                        <Route path="/appraisal/:id" element={<ProtectedRoute><AppraisalPage /></ProtectedRoute>} />
                        <Route path="/profile" element={<ProtectedRoute><UserProfile /></ProtectedRoute>} />
                    </Routes>
                </Router>
            </SocketProvider>
        </AuthProvider>
    );
}

export default App;
