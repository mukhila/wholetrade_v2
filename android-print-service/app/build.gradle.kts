plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "com.techgeeks.wholetradeprint"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.techgeeks.wholetradeprint"
        minSdk = 24
        targetSdk = 34
        versionCode = 1
        versionName = "1.0"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions { jvmTarget = "17" }
}

// Zero external dependencies on purpose:
// networking = HttpURLConnection, JSON = org.json, UI = plain android.app.Activity.
dependencies {}
