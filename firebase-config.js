// ─────────────────────────────────────────────────────────────────────────────
// STEP 1 — Go to https://console.firebase.google.com
// STEP 2 — Create a project (or open an existing one)
// STEP 3 — Click "Add app" → Web (</>)
// STEP 4 — Copy the firebaseConfig object below and replace the placeholders
// STEP 5 — In the Firebase console: Build → Realtime Database → Create database
//           Choose "Start in test mode" (allows open read/write for now)
// ─────────────────────────────────────────────────────────────────────────────

const firebaseConfig = {
    apiKey:            "YOUR_API_KEY",
    authDomain:        "YOUR_PROJECT_ID.firebaseapp.com",
    databaseURL:       "https://YOUR_PROJECT_ID-default-rtdb.firebaseio.com",
    projectId:         "YOUR_PROJECT_ID",
    storageBucket:     "YOUR_PROJECT_ID.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId:             "YOUR_APP_ID"
};

firebase.initializeApp(firebaseConfig);
