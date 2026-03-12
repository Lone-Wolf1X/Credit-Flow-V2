<!-- Enhanced Person Selector Modal with Table View -->
<div class="modal fade" id="personSelectorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-check"></i> Select Existing Person
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="personSearchInput" 
                                           placeholder="Search by name, citizenship number, or father's name...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary btn-lg btn-block" type="button" onclick="searchPersonsTable()">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div id="personSearchLoading" class="text-center py-5" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3">Searching...</p>
                </div>

                <!-- Results Table -->
                <div id="personTableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="personTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Full Name</th>
                                    <th width="15%">Citizenship No.</th>
                                    <th width="12%">Date of Birth</th>
                                    <th width="18%">Father's Name</th>
                                    <th width="10%">Type</th>
                                    <th width="10%">Profile</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="personTableBody">
                                <!-- Results will be inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span class="text-muted">Showing <strong id="showingFrom">0</strong> to <strong id="showingTo">0</strong> of <strong id="totalRecords">0</strong> records</span>
                        </div>
                        <nav>
                            <ul class="pagination mb-0" id="paginationControls">
                                <!-- Pagination buttons will be inserted here -->
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Initial Message -->
                <div id="personSearchInitial" class="text-center py-5">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Enter search term above to find existing persons</p>
                </div>

                <!-- No Results Message -->
                <div id="personSearchNoResults" class="text-center py-5" style="display: none;">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No persons found matching your search</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Person Selector Styles */
#personTable {
    font-size: 14px;
}

#personTable thead th {
    position: sticky;
    top: 0;
    background-color: #343a40;
    color: white;
    z-index: 10;
}

#personTable tbody tr {
    cursor: pointer;
    transition: all 0.2s ease;
}

#personTable tbody tr:hover {
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.person-name {
    font-weight: 600;
    color: #333;
}

.person-name-en {
    font-size: 12px;
    color: #666;
    display: block;
}

.badge-person-type {
    font-size: 11px;
}

.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

.pagination {
    margin-bottom: 0;
}

.page-link {
    cursor: pointer;
}

#personSearchInput {
    border: 2px solid #dee2e6;
}

#personSearchInput:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
</style>
