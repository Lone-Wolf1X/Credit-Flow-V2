/**
 * Multi-Step Form Handler
 * Handles navigation and validation for multi-step forms
 */

class MultiStepForm {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.steps = this.form.querySelectorAll('.form-step');
        this.currentStep = 0;
        this.totalSteps = this.steps.length;

        this.init();
    }

    init() {
        // Hide all steps except first
        this.steps.forEach((step, index) => {
            if (index !== 0) {
                step.style.display = 'none';
            }
        });

        // Update progress
        this.updateProgress();

        // Add navigation button listeners
        this.form.querySelectorAll('.btn-next').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.nextStep();
            });
        });

        this.form.querySelectorAll('.btn-prev').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.prevStep();
            });
        });
    }

    nextStep() {
        // Validate current step
        if (!this.validateStep(this.currentStep)) {
            return;
        }

        // Save current step data to sessionStorage
        this.saveStepData(this.currentStep);

        // Hide current step
        this.steps[this.currentStep].style.display = 'none';

        // Show next step
        this.currentStep++;
        this.steps[this.currentStep].style.display = 'block';

        // Update progress
        this.updateProgress();

        // Scroll to top
        window.scrollTo(0, 0);
    }

    prevStep() {
        // Hide current step
        this.steps[this.currentStep].style.display = 'none';

        // Show previous step
        this.currentStep--;
        this.steps[this.currentStep].style.display = 'block';

        // Update progress
        this.updateProgress();

        // Scroll to top
        window.scrollTo(0, 0);
    }

    validateStep(stepIndex) {
        const step = this.steps[stepIndex];
        const inputs = step.querySelectorAll('input[required], select[required], textarea[required]');

        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');

                // Add error message if not exists
                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                    const error = document.createElement('div');
                    error.className = 'invalid-feedback';
                    error.textContent = 'This field is required / यो फिल्ड आवश्यक छ';
                    input.parentNode.appendChild(error);
                }
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert('Please fill all required fields / कृपया सबै आवश्यक फिल्डहरू भर्नुहोस्');
        }

        return isValid;
    }

    saveStepData(stepIndex) {
        const step = this.steps[stepIndex];
        const inputs = step.querySelectorAll('input, select, textarea');
        const data = {};

        inputs.forEach(input => {
            if (input.name) {
                data[input.name] = input.value;
            }
        });

        sessionStorage.setItem(`form_step_${stepIndex}`, JSON.stringify(data));
    }

    loadStepData(stepIndex) {
        const savedData = sessionStorage.getItem(`form_step_${stepIndex}`);

        if (savedData) {
            const data = JSON.parse(savedData);
            const step = this.steps[stepIndex];

            Object.keys(data).forEach(key => {
                const input = step.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key];
                }
            });
        }
    }

    updateProgress() {
        const progress = ((this.currentStep + 1) / this.totalSteps) * 100;
        const progressBar = this.form.querySelector('.progress-bar');

        if (progressBar) {
            progressBar.style.width = progress + '%';
            progressBar.textContent = `Step ${this.currentStep + 1} of ${this.totalSteps}`;
        }

        // Update step indicators
        const indicators = this.form.querySelectorAll('.step-indicator');
        indicators.forEach((indicator, index) => {
            if (index < this.currentStep) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (index === this.currentStep) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });
    }

    clearSessionData() {
        for (let i = 0; i < this.totalSteps; i++) {
            sessionStorage.removeItem(`form_step_${i}`);
        }
    }

    getAllFormData() {
        const formData = new FormData(this.form);
        return formData;
    }
}

// Helper function to add dynamic rows (for family details, authorized persons)
function addDynamicRow(containerId, template) {
    const container = document.getElementById(containerId);
    const rowCount = container.querySelectorAll('.dynamic-row').length;

    const newRow = document.createElement('div');
    newRow.className = 'dynamic-row row g-3 mb-3';
    newRow.innerHTML = template.replace(/\{index\}/g, rowCount);

    container.appendChild(newRow);
}

function removeDynamicRow(button) {
    const row = button.closest('.dynamic-row');
    row.remove();
}
