package com.phonemonitor.app.ui

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import com.phonemonitor.app.R
import com.phonemonitor.app.data.DevicePreferences

class MainActivity : AppCompatActivity() {
    
    private lateinit var prefs: DevicePreferences
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        
        prefs = DevicePreferences(this)
        
        setupUI()
    }
    
    private fun setupUI() {
        val statusText = findViewById<TextView>(R.id.statusText)
        val deviceInfoText = findViewById<TextView>(R.id.deviceInfoText)
        val settingsButton = findViewById<Button>(R.id.settingsButton)
        
        statusText.text = "Family sharing is active"
        
        val info = """
            Device: ${prefs.displayName}
            Owner: ${prefs.ownerName}
            UUID: ${prefs.deviceUuid}
            Server: ${prefs.serverUrl}
            Location Sharing: ${if (prefs.locationEnabled) "Enabled" else "Disabled"}
        """.trimIndent()
        
        deviceInfoText.text = info
        
        settingsButton.setOnClickListener {
            startActivity(Intent(this, SettingsActivity::class.java))
        }
    }
    
    override fun onResume() {
        super.onResume()
        setupUI() // Refresh UI in case settings changed
    }
}
