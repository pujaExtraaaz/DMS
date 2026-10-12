<?php

// The TallyPrime office connector is dormant (not required for day-to-day Books).
// Native Books (`routes/modules/books.php`) is the live accounting path.
//
// Future re-enable (kept ready):
// 1. TALLY_SYNC_ENABLED=true
// 2. TALLY_CONNECTOR_TOKEN + TALLY_COMPANY
// 3. Admin UI: books.tally.connector.show
// 4. Public API auto-loads from routes/web.php when enabled
// 5. TallySyncServiceProvider registers TallyAutoEnqueueObserver when enabled
//
// Templates: App\Domains\TallySync\Templates
// Config: config/tally_sync.php
