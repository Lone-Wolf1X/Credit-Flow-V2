<?php
/**
 * Shared Address Section Component
 * Used by both Individual and Corporate forms
 */
?>

<!-- Permanent/Registered Address -->
<h6 class="fw-bold mb-3 text-primary"><?php echo $addressLabels['permanent'] ?? 'Permanent Address / स्थायी ठेगाना'; ?></h6>
<div class="row g-3 mb-4">
    <!-- Hidden inputs to ensure values are submitted -->
    <input type="hidden" id="perm_province_hidden" name="perm_province" value="<?php echo $data['perm_province'] ?? ''; ?>">
    <input type="hidden" id="perm_district_hidden" name="perm_district" value="<?php echo $data['perm_district'] ?? ''; ?>">
    
    <div class="col-md-3">
        <label class="form-label fw-semibold">Province / प्रदेश</label>
        <select class="form-select" id="perm_province" onchange="updateHiddenField('perm_province', this.value)">
            <option value="">Select / छान्नुहोस्</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">District / जिल्ला</label>
        <select class="form-select" id="perm_district" onchange="updateHiddenField('perm_district', this.value)" disabled>
            <option value="">Select / छान्नुहोस्</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Municipality/VDC / नगरपालिका/गाउँपालिका</label>
        <input type="text" class="form-control nepali-input preeti-font" id="perm_municipality_vdc" name="perm_municipality_vdc" value="<?php echo $data['perm_municipality_vdc'] ?? ''; ?>" placeholder=" नगरपालिका/गाउँपालिका" style="font-size: 1.2rem;">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Ward No / वडा नं (१-९९)</label>
        <input type="text" class="form-control nepali-input preeti-font" id="perm_ward_no" name="perm_ward_no" value="<?php echo $data['perm_ward_no'] ?? ''; ?>" placeholder="वडा नं" style="font-size: 1.2rem;">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Tole/Street / टोल/सडक</label>
        <input type="text" class="form-control nepali-input preeti-font" name="perm_street_name" value="<?php echo $data['perm_street_name'] ?? ''; ?>" style="font-size: 1.2rem;">
    </div>
</div>

<!-- Temporary/Branch Address -->
<h6 class="fw-bold mb-3 text-primary"><?php echo $addressLabels['temporary'] ?? 'Temporary Address / अस्थायी ठेगाना'; ?></h6>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="sameAsPermanent" onchange="toggleSameAddress()">
    <label class="form-check-label" for="sameAsPermanent">
        <?php echo $addressLabels['sameAs'] ?? 'Same as Permanent Address / स्थायी ठेगाना जस्तै'; ?>
    </label>
</div>
<div class="row g-3">
    <!-- Hidden inputs for temporary address -->
    <input type="hidden" id="temp_province_hidden" name="temp_province" value="<?php echo $data['temp_province'] ?? ''; ?>">
    <input type="hidden" id="temp_district_hidden" name="temp_district" value="<?php echo $data['temp_district'] ?? ''; ?>">
    <input type="hidden" id="temp_municipality_vdc_hidden" name="temp_municipality_vdc" value="<?php echo $data['temp_municipality_vdc'] ?? ''; ?>">
    <input type="hidden" id="temp_ward_no_hidden" name="temp_ward_no" value="<?php echo $data['temp_ward_no'] ?? ''; ?>">
    
    <div class="col-md-3">
        <label class="form-label fw-semibold">Province / प्रदेश</label>
        <select class="form-select" id="temp_province" onchange="updateHiddenField('temp_province', this.value)">
            <option value="">Select / छान्नुहोस्</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">District / जिल्ला</label>
        <select class="form-select" id="temp_district" onchange="updateHiddenField('temp_district', this.value)" disabled>
            <option value="">Select / छान्नुहोस्</option>
        </select>
    </div>
    
    <!-- Temporary: Municipality Wrapper (Select or Input) -->
    <div class="col-md-3">
        <label class="form-label fw-semibold">Municipality/VDC / नगरपालिका/गाउँपालिका</label>
        <div id="temp_municipality_container">
            <select class="form-select" id="temp_municipality_vdc" onchange="updateHiddenField('temp_municipality_vdc', this.value)" disabled>
                <option value="">Select / छान्नुहोस्</option>
            </select>
        </div>
        <input type="text" class="form-control nepali-input preeti-font d-none" id="temp_municipality_vdc_text" placeholder="नगरपालिका/गाउँपालिका" style="font-size: 1.2rem;">
    </div>

    <!-- Temporary: Ward Wrapper (Select or Input) -->
    <div class="col-md-3">
        <label class="form-label fw-semibold">Ward No / वडा नं</label>
        <div id="temp_ward_container">
            <select class="form-select" id="temp_ward_no" onchange="updateHiddenField('temp_ward_no', this.value)" disabled>
                <option value="">Select / छान्नुहोस्</option>
            </select>
        </div>
        <input type="text" class="form-control nepali-input preeti-font d-none" id="temp_ward_no_text" placeholder="वडा नं" style="font-size: 1.2rem;">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Tole/Street / टोल/सडक</label>
        <input type="text" class="form-control nepali-input preeti-font" id="temp_street_name" name="temp_street_name" value="<?php echo $data['temp_street_name'] ?? ''; ?>" style="font-size: 1.2rem;">
    </div>
</div>
