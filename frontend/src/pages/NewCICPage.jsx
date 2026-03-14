import React, { useState } from 'react';
import { 
    Plus, Trash2, Send, ArrowLeft, ArrowRight, Save, User, Building, 
    FileText, CheckCircle, Info, Upload, MapPin, Users, Hash
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import api from '../api';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';

const NewCICPage = () => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [step, setStep] = useState(1);
    const [submitting, setSubmitting] = useState(false);

    // Initial States
    const emptyPersonal = {
        full_name: '', full_name_np: '', 
        date_of_birth: '', date_of_birth_ad: '',
        gender: 'Male', relationship_status: 'Single',
        father_name: '', grandfather_name: '', spouse_name: '',
        citizenship_number: '', citizenship_issued_district: '', citizenship_issued_date: '', id_issue_authority: 'जिल्ला प्रशासन कार्यालय',
        ctz_reissued: false, id_reissue_date: '', reissue_count: '0',
        pan_number: '', 
        permanent_province: '', permanent_district: '', permanent_municipality: '', permanent_ward: '', permanent_street: '',
        current_province: '', current_district: '', current_municipality: '', current_ward: '', current_street: '',
        contact_number: '', email: '', occupation: '',
        family_members: [{ name: '', relation: '' }]
    };

    const emptyInstitutional = {
        business_name: '', registration_number: '', registration_date: '', registration_authority: '', registration_type: 'Private Limited',
        pan_vat_number: '', pan_issue_date: '', pan_issue_authority: '', firm_registration_authority: '',
        business_type: '',
        registered_province: '', registered_district: '', registered_municipality: '', registered_ward: '', registered_street: '',
        operating_province: '', operating_district: '', operating_municipality: '', operating_ward: '', operating_street: '',
        contact_number: '', email: '', main_activities: '',
        authorized_persons: [{ full_name: '', designation: '', contact: '' }]
    };

    const [header, setHeader] = useState({
        transaction_id: '',
        initiator_comment: '',
        docs: []
    });

    const [entities, setEntities] = useState([
        { entity_type: 'Personal', details: { ...emptyPersonal } }
    ]);

    // Handlers
    const handleAddEntity = () => {
        setEntities([...entities, { entity_type: 'Personal', details: { ...emptyPersonal } }]);
    };

    const handleRemoveEntity = (idx) => {
        setEntities(entities.filter((_, i) => i !== idx));
    };

    const handleEntityChange = (idx, field, value) => {
        const newEntities = [...entities];
        newEntities[idx].details[field] = value;
        setEntities(newEntities);
    };

    const handleFamilyMemberChange = (eIdx, mIdx, field, value) => {
        const newEntities = [...entities];
        newEntities[eIdx].details.family_members[mIdx][field] = value;
        setEntities(newEntities);
    };

    const addFamilyMember = (eIdx) => {
        const newEntities = [...entities];
        newEntities[eIdx].details.family_members.push({ name: '', relation: '' });
        setEntities(newEntities);
    };

    const removeFamilyMember = (eIdx, mIdx) => {
        const newEntities = [...entities];
        newEntities[eIdx].details.family_members = newEntities[eIdx].details.family_members.filter((_, i) => i !== mIdx);
        setEntities(newEntities);
    };

    const handleTypeChange = (idx, type) => {
        const newEntities = [...entities];
        newEntities[idx].entity_type = type;
        newEntities[idx].details = type === 'Personal' ? { ...emptyPersonal } : { ...emptyInstitutional };
        setEntities(newEntities);
    };

    const calculateTotals = () => {
        const base = entities.length * 250;
        const vat = base * 0.13;
        return { base, vat, total: base + vat };
    };

    const { total } = calculateTotals();

    const handleSubmit = async (targetStatus) => {
        if (targetStatus === 'Submitted' && !header.transaction_id) {
            return toast.error('Transaction ID is required for final submission');
        }

        // Subject Validation
        for (const ent of entities) {
            const name = ent.entity_type === 'Personal' ? ent.details.full_name : ent.details.business_name;
            if (!name) return toast.error(`Subject ${entities.indexOf(ent) + 1} name is required`);
        }

        try {
            setSubmitting(true);
            const payload = {
                lead_id: null,
                entities: entities,
                transaction_id: header.transaction_id,
                initiator_comment: header.initiator_comment,
                initiator_docs_url: header.docs.filter(d => d.url),
                status: targetStatus
            };

            await api.post('/cics/initiate', payload);
            toast.success(`CIC Request ${targetStatus === 'Draft' ? 'Saved as Draft' : 'Submitted Successfully'}!`);
            navigate('/cic');
        } catch (err) {
            toast.error(err.response?.data?.error || 'Failed to initiate request');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="container" style={{ maxWidth: '1200px', padding: '40px 20px' }}>
            {/* Header */}
            <header style={{ marginBottom: '40px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }}>
                    <button onClick={() => navigate('/cic')} style={{ display: 'flex', alignItems: 'center', gap: '8px', border: 'none', background: 'none', color: 'var(--primary)', fontWeight: '700', cursor: 'pointer', marginBottom: '15px' }}>
                        <ArrowLeft size={18} /> Back to Hub
                    </button>
                    <h1 style={{ fontSize: '2.5rem', fontWeight: '900', margin: 0, color: '#1e293b', display: 'flex', alignItems: 'center', gap: '15px' }}>
                        <FileText size={40} color="var(--primary)" /> New Standalone CIC
                    </h1>
                    <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem' }}>Enter subject details from DAS forms for precise profiling</p>
                </motion.div>

                {/* Stepper UI */}
                <div style={{ display: 'flex', gap: '10px' }}>
                    {[1, 2].map(s => (
                        <div key={s} style={{ 
                            width: '40px', height: '40px', borderRadius: '50%', background: step >= s ? 'var(--primary)' : '#e2e8f0', 
                            color: step >= s ? 'white' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center', 
                            fontWeight: '800', border: step === s ? '4px solid #dbeafe' : 'none', transition: 'all 0.3s'
                        }}>
                            {s}
                        </div>
                    ))}
                </div>
            </header>

            <AnimatePresence mode="wait">
                {step === 1 ? (
                    <motion.div 
                        key="step1" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }}
                        style={{ background: 'white', borderRadius: '24px', padding: '40px', boxShadow: '0 10px 25px rgba(0,0,0,0.05)', border: '1px solid #e2e8f0' }}
                    >
                        <h3 style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '30px', fontWeight: '800' }}>
                            <Info size={24} color="var(--primary)" /> Request Meta Information
                        </h3>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: '30px' }}>
                            <div className="form-group">
                                <label>Originating Branch</label>
                                <input type="text" className="form-input" value={user?.branch_name} disabled style={{ background: '#f8fafc', fontWeight: '700' }} />
                            </div>
                            <div className="form-group">
                                <label style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    CBS Transaction Reference <Info size={14} title="Required for HO submission" />
                                </label>
                                <input 
                                    type="text" className="form-input" placeholder="e.g. TRN12345678" 
                                    value={header.transaction_id} onChange={e => setHeader({...header, transaction_id: e.target.value})}
                                    style={{ border: '2px solid #cbd5e1' }}
                                />
                            </div>
                        </div>

                        <div className="form-group" style={{ marginTop: '20px' }}>
                            <label>Supporting Documents / Links</label>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', background: '#f8fafc', padding: '20px', borderRadius: '15px' }}>
                                {header.docs.length === 0 && <p style={{ textAlign: 'center', color: '#64748b', fontSize: '0.9rem' }}>No documents attached yet.</p>}
                                {header.docs.map((doc, idx) => (
                                    <div key={idx} style={{ display: 'flex', gap: '10px' }}>
                                        <input placeholder="Doc Label" className="form-input" style={{ flex: 1 }} value={doc.name} onChange={e => {
                                            const newDocs = [...header.docs];
                                            newDocs[idx].name = e.target.value;
                                            setHeader({...header, docs: newDocs});
                                        }} />
                                        <input placeholder="Public URL or Reference" className="form-input" style={{ flex: 2 }} value={doc.url} onChange={e => {
                                            const newDocs = [...header.docs];
                                            newDocs[idx].url = e.target.value;
                                            setHeader({...header, docs: newDocs});
                                        }} />
                                        <button onClick={() => setHeader({...header, docs: header.docs.filter((_, i) => i !== idx)})} style={{ border: 'none', background: '#fee2e2', color: '#dc2626', padding: '8px', borderRadius: '8px' }}><Trash2 size={16} /></button>
                                    </div>
                                ))}
                                <button className="btn btn-secondary btn-sm" onClick={() => setHeader({...header, docs: [...header.docs, { name: '', url: '' }]})} style={{ width: 'fit-content' }}>
                                    <Plus size={14} /> Add Attachment Line
                                </button>
                            </div>
                        </div>

                        <div className="form-group" style={{ marginTop: '20px' }}>
                            <label>Processor Remarks (Internal Instructions)</label>
                            <textarea 
                                className="form-input" rows="3" placeholder="Explain the urgency or special case details..."
                                value={header.initiator_comment} onChange={e => setHeader({...header, initiator_comment: e.target.value})}
                            />
                        </div>

                        <div style={{ marginTop: '40px', display: 'flex', justifyContent: 'flex-end' }}>
                            <button className="btn btn-primary btn-lg" onClick={() => setStep(2)}>
                                Next: Subject Details <ArrowRight size={18} />
                            </button>
                        </div>
                    </motion.div>
                ) : (
                    <motion.div 
                        key="step2" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }}
                        style={{ display: 'flex', flexDirection: 'column', gap: '30px' }}
                    >
                        {entities.map((ent, idx) => (
                            <div key={idx} style={{ background: 'white', borderRadius: '24px', padding: '40px', boxShadow: '0 10px 25px rgba(0,0,0,0.05)', border: '1px solid #e2e8f0', position: 'relative' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                                    <h3 style={{ margin: 0, display: 'flex', alignItems: 'center', gap: '10px', fontWeight: '800' }}>
                                        {ent.entity_type === 'Personal' ? <User size={24} color="var(--primary)" /> : <Building size={24} color="var(--primary)" />}
                                        Subject #{idx + 1}: {ent.entity_type}
                                    </h3>
                                    <div style={{ display: 'flex', gap: '10px' }}>
                                        <button className={`btn btn-sm ${ent.entity_type === 'Personal' ? 'btn-primary' : 'btn-secondary'}`} onClick={() => handleTypeChange(idx, 'Personal')}>Individual</button>
                                        <button className={`btn btn-sm ${ent.entity_type === 'Institutional' ? 'btn-primary' : 'btn-secondary'}`} onClick={() => handleTypeChange(idx, 'Institutional')}>Institutional</button>
                                        {entities.length > 1 && (
                                            <button onClick={() => handleRemoveEntity(idx)} style={{ background: '#fee2e2', color: '#dc2626', border: 'none', padding: '10px', borderRadius: '12px', cursor: 'pointer' }}><Trash2 size={20} /></button>
                                        )}
                                    </div>
                                </div>

                                {ent.entity_type === 'Personal' ? (
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '30px' }}>
                                        {/* Name Row */}
                                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                                            <div className="form-group">
                                                <label>Full Name (English) <span style={{ color: 'red' }}>*</span></label>
                                                <input type="text" className="form-input" placeholder="AS PER CITIZENSHIP" value={ent.details.full_name} onChange={e => handleEntityChange(idx, 'full_name', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>पूरा नाम (नेपाली)</label>
                                                <input type="text" className="form-input" placeholder="पूरा नाम" value={ent.details.full_name_np} onChange={e => handleEntityChange(idx, 'full_name_np', e.target.value)} />
                                            </div>
                                        </div>

                                        {/* DOB Row */}
                                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: '20px' }}>
                                            <div className="form-group">
                                                <label>Date of Birth (BS)</label>
                                                <input type="text" className="form-input" placeholder="YYYY-MM-DD" value={ent.details.date_of_birth} onChange={e => handleEntityChange(idx, 'date_of_birth', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>DOB (AD)</label>
                                                <input type="date" className="form-input" value={ent.details.date_of_birth_ad} onChange={e => handleEntityChange(idx, 'date_of_birth_ad', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>Gender</label>
                                                <select className="form-input" value={ent.details.gender} onChange={e => handleEntityChange(idx, 'gender', e.target.value)}>
                                                    <option>Male</option>
                                                    <option>Female</option>
                                                    <option>Other</option>
                                                </select>
                                            </div>
                                            <div className="form-group">
                                                <label>Relationship</label>
                                                <select className="form-input" value={ent.details.relationship_status} onChange={e => handleEntityChange(idx, 'relationship_status', e.target.value)}>
                                                    <option>Single</option>
                                                    <option>Married</option>
                                                    <option>Divorced</option>
                                                    <option>Widowed</option>
                                                </select>
                                            </div>
                                        </div>

                                        {/* Family Row */}
                                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '20px' }}>
                                            <div className="form-group">
                                                <label>Father's Name</label>
                                                <input type="text" className="form-input" value={ent.details.father_name} onChange={e => handleEntityChange(idx, 'father_name', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>Grandfather's Name</label>
                                                <input type="text" className="form-input" value={ent.details.grandfather_name} onChange={e => handleEntityChange(idx, 'grandfather_name', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>Spouse Name</label>
                                                <input type="text" className="form-input" value={ent.details.spouse_name} onChange={e => handleEntityChange(idx, 'spouse_name', e.target.value)} />
                                            </div>
                                        </div>

                                        {/* Citizenship Row */}
                                        <div style={{ padding: '20px', background: '#f0f9ff', borderRadius: '15px', border: '1px solid #bae6fd' }}>
                                            <h5 style={{ margin: '0 0 15px 0', fontSize: '0.9rem', fontWeight: '800', color: '#0369a1', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                                <Hash size={16} /> Identity Document (Citizenship)
                                            </h5>
                                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '15px' }}>
                                                <div className="form-group">
                                                    <label>Number</label>
                                                    <input type="text" className="form-input" value={ent.details.citizenship_number} onChange={e => handleEntityChange(idx, 'citizenship_number', e.target.value)} />
                                                </div>
                                                <div className="form-group">
                                                    <label>Issue District</label>
                                                    <input type="text" className="form-input" value={ent.details.citizenship_issued_district} onChange={e => handleEntityChange(idx, 'citizenship_issued_district', e.target.value)} />
                                                </div>
                                                <div className="form-group">
                                                    <label>Issue Date (BS)</label>
                                                    <input type="text" className="form-input" value={ent.details.citizenship_issued_date} onChange={e => handleEntityChange(idx, 'citizenship_issued_date', e.target.value)} />
                                                </div>
                                                <div className="form-group">
                                                    <label>Authority</label>
                                                    <select className="form-input" value={ent.details.id_issue_authority} onChange={e => handleEntityChange(idx, 'id_issue_authority', e.target.value)}>
                                                        <option>जिल्ला प्रशासन कार्यालय</option>
                                                        <option>इलाका प्रशासन कार्यालय</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Address Row */}
                                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '30px' }}>
                                            <div>
                                                <h5 style={{ fontSize: '0.95rem', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '15px' }}>
                                                    <MapPin size={18} color="#64748b" /> Permanent Address
                                                </h5>
                                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                                                    <input placeholder="Province" className="form-input" value={ent.details.permanent_province} onChange={e => handleEntityChange(idx, 'permanent_province', e.target.value)} />
                                                    <input placeholder="District" className="form-input" value={ent.details.permanent_district} onChange={e => handleEntityChange(idx, 'permanent_district', e.target.value)} />
                                                    <input placeholder="Municipality" className="form-input" value={ent.details.permanent_municipality} onChange={e => handleEntityChange(idx, 'permanent_municipality', e.target.value)} />
                                                    <input placeholder="Ward No" className="form-input" value={ent.details.permanent_ward} onChange={e => handleEntityChange(idx, 'permanent_ward', e.target.value)} />
                                                    <input placeholder="Street / Tole" className="form-input" style={{ gridColumn: 'span 2' }} value={ent.details.permanent_street} onChange={e => handleEntityChange(idx, 'permanent_street', e.target.value)} />
                                                </div>
                                            </div>
                                            <div>
                                                <h5 style={{ fontSize: '0.95rem', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '15px' }}>
                                                    <MapPin size={18} color="#64748b" /> Current Address
                                                </h5>
                                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                                                    <input placeholder="Province" className="form-input" value={ent.details.current_province} onChange={e => handleEntityChange(idx, 'current_province', e.target.value)} />
                                                    <input placeholder="District" className="form-input" value={ent.details.current_district} onChange={e => handleEntityChange(idx, 'current_district', e.target.value)} />
                                                    <input placeholder="Municipality" className="form-input" value={ent.details.current_municipality} onChange={e => handleEntityChange(idx, 'current_municipality', e.target.value)} />
                                                    <input placeholder="Ward No" className="form-input" value={ent.details.current_ward} onChange={e => handleEntityChange(idx, 'current_ward', e.target.value)} />
                                                    <input placeholder="Street / Tole" className="form-input" style={{ gridColumn: 'span 2' }} value={ent.details.current_street} onChange={e => handleEntityChange(idx, 'current_street', e.target.value)} />
                                                </div>
                                            </div>
                                        </div>

                                        {/* Family Members List */}
                                        <div style={{ marginTop: '10px' }}>
                                            <h5 style={{ fontSize: '0.95rem', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '15px' }}>
                                                <Users size={18} color="#64748b" /> Family Members / परिवारका सदस्यहरू
                                            </h5>
                                            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                                                {ent.details.family_members.map((m, mIdx) => (
                                                    <div key={mIdx} style={{ display: 'flex', gap: '15px' }}>
                                                        <input placeholder="Member Name" className="form-input" style={{ flex: 1 }} value={m.name} onChange={e => handleFamilyMemberChange(idx, mIdx, 'name', e.target.value)} />
                                                        <select className="form-input" style={{ flex: 1 }} value={m.relation} onChange={e => handleFamilyMemberChange(idx, mIdx, 'relation', e.target.value)}>
                                                            <option value="">Relation / नाता</option>
                                                            <option>Father / बुबा</option>
                                                            <option>Mother / आमा</option>
                                                            <option>Spouse / पति/पत्नी</option>
                                                            <option>Son / छोरा</option>
                                                            <option>Daughter / छोरी</option>
                                                            <option>Grandfather / बाजे</option>
                                                            <option>Grandmother / बज्यै</option>
                                                        </select>
                                                        {ent.details.family_members.length > 1 && (
                                                            <button onClick={() => removeFamilyMember(idx, mIdx)} style={{ border: 'none', background: '#f1f5f9', color: '#64748b', padding: '10px', borderRadius: '10px' }}><Trash2 size={16} /></button>
                                                        )}
                                                    </div>
                                                ))}
                                                <button className="btn btn-secondary btn-sm" onClick={() => addFamilyMember(idx)} style={{ width: 'fit-content' }}>+ Add Member</button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '30px' }}>
                                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '20px' }}>
                                            <div className="form-group">
                                                <label>Business Name</label>
                                                <input type="text" className="form-input" value={ent.details.business_name} onChange={e => handleEntityChange(idx, 'business_name', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>Registration Number</label>
                                                <input type="text" className="form-input" value={ent.details.registration_number} onChange={e => handleEntityChange(idx, 'registration_number', e.target.value)} />
                                            </div>
                                            <div className="form-group">
                                                <label>Registration Type</label>
                                                <select className="form-input" value={ent.details.registration_type} onChange={e => handleEntityChange(idx, 'registration_type', e.target.value)}>
                                                    <option>Private Limited</option>
                                                    <option>Public Limited</option>
                                                    <option>Partnership</option>
                                                    <option>Proprietorship</option>
                                                </select>
                                            </div>
                                        </div>
                                        {/* More Institutional fields can be added here following same pattern */}
                                    </div>
                                )}
                            </div>
                        ))}

                        <button 
                            onClick={handleAddEntity}
                            style={{ padding: '20px', borderRadius: '20px', border: '2px dashed #cbd5e1', background: 'rgba(255,255,255,0.5)', color: '#64748b', fontWeight: '700', fontSize: '1.1rem', cursor: 'pointer', transition: 'all 0.2s', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '10px' }}
                            onMouseOver={e => { e.currentTarget.style.borderColor = 'var(--primary)'; e.currentTarget.style.color = 'var(--primary)'; }}
                            onMouseOut={e => { e.currentTarget.style.borderColor = '#cbd5e1'; e.currentTarget.style.color = '#64748b'; }}
                        >
                            <Plus size={24} /> Add Another Subject to This Request
                        </button>

                        {/* Footer Summary & Actions */}
                        <div style={{ background: 'white', borderRadius: '24px', padding: '30px', boxShadow: '0 10px 25px rgba(0,0,0,0.05)', border: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <div>
                                <p style={{ margin: 0, fontSize: '0.9rem', color: '#64748b', fontWeight: '600' }}>TOTAL PROCESSING FEE (Incl. 13% VAT)</p>
                                <h1 style={{ margin: 0, fontWeight: '900', color: '#0f172a' }}>रु {total.toLocaleString()}</h1>
                            </div>
                            <div style={{ display: 'flex', gap: '15px' }}>
                                <button className="btn btn-secondary btn-lg" onClick={() => setStep(1)}>Back</button>
                                <button className="btn btn-secondary btn-lg" style={{ background: 'white', border: '1px solid #e2e8f0' }} onClick={() => handleSubmit('Draft')} disabled={submitting}>
                                    <Save size={20} /> Save as Draft
                                </button>
                                <button className="btn btn-primary btn-lg" onClick={() => handleSubmit('Submitted')} disabled={submitting}>
                                    <Send size={20} /> {submitting ? 'Submitting...' : 'Submit to Head Office'}
                                </button>
                            </div>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
};

export default NewCICPage;
