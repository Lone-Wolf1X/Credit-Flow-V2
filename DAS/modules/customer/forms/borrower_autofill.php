<!-- Auto-fill from Person Selector -->
<script>
// Check if we're returning from person selector with data
window.addEventListener('DOMContentLoaded', function() {
    const selectedPersonData = sessionStorage.getItem('selectedPersonData');
    
    if (selectedPersonData) {
        try {
            const person = JSON.parse(selectedPersonData);
            
            // Clear the session storage
            sessionStorage.removeItem('selectedPersonData');
            
            console.log('Auto-filling form with person data:', person);
            
            // Fill form fields
            const form = document.getElementById('borrowerForm');
            if (form) {
                // Personal details
                const fields = {
                    'full_name': person.full_name,
                    'full_name_en': person.full_name_en,
                    'date_of_birth': person.date_of_birth,
                    'gender': person.gender,
                    'relationship_status': person.relationship_status,
                    'citizenship_number': person.citizenship_number,
                    'id_issue_date': person.id_issue_date,
                    'id_issue_district': person.id_issue_district,
                    'id_issue_authority': person.id_issue_authority,
                    'id_reissue_date': person.id_reissue_date,
                    'reissue_count': person.reissue_count,
                    'perm_province': person.perm_province,
                    'perm_district': person.perm_district,
                    'perm_municipality_vdc': person.perm_municipality_vdc,
                    'perm_ward_no': person.perm_ward_no,
                    'perm_town_village': person.perm_town_village,
                    'perm_street_name': person.perm_street_name,
                    'temp_province': person.temp_province,
                    'temp_district': person.temp_district,
                    'temp_municipality_vdc': person.temp_municipality_vdc,
                    'temp_ward_no': person.temp_ward_no,
                    'temp_town_village': person.temp_town_village,
                    'contact_number': person.contact_number,
                    'email': person.email
                };
                
                Object.keys(fields).forEach(fieldName => {
                    const input = form.querySelector(`[name="${fieldName}"]`);
                    if (input && fields[fieldName]) {
                        input.value = fields[fieldName];
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                
                // Fill family details if available
                if (person.family_details && person.family_details.length > 0) {
                    person.family_details.forEach(family => {
                        const relation = family.relation.toLowerCase();
                        const name = family.name;
                        
                        const familyFieldMap = {
                            'father': 'father_name',
                            'बुबा': 'father_name',
                            'mother': 'mother_name',
                            'आमा': 'mother_name',
                            'grandfather': 'grandfather_name',
                            'बाजे': 'grandfather_name',
                            'spouse': 'spouse_name',
                            'husband': 'spouse_name',
                            'wife': 'spouse_name',
                            'पति': 'spouse_name',
                            'पत्नी': 'spouse_name'
                        };
                        
                        const formFieldName = familyFieldMap[relation];
                        if (formFieldName) {
                            const familyInput = form.querySelector(`[name="${formFieldName}"]`);
                            if (familyInput) {
                                familyInput.value = name;
                                familyInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    });
                }
                
                // Show success message
                alert('✅ Person data loaded successfully! Please review and save.');
            }
        } catch (e) {
            console.error('Error loading person data:', e);
            alert('Error loading person data. Please try again.');
        }
    }
});
</script>
