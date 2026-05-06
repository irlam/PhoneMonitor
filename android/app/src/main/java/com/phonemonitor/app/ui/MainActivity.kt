package com.phonemonitor.app.ui

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.phonemonitor.app.R
import com.phonemonitor.app.data.DevicePreferences
import com.phonemonitor.app.network.PingRequest
import com.phonemonitor.app.network.RetrofitClient
import com.phonemonitor.app.utils.DeviceUtils
import com.phonemonitor.app.utils.LocationHelper
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

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
        val sendNowButton = findViewById<Button>(R.id.sendNowButton)
        
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

        sendNowButton.setOnClickListener {
            sendNowButton.isEnabled = false
            sendUpdateNow(sendNowButton)
        }
    }

    private fun sendUpdateNow(button: Button) {
        lifecycleScope.launch {
            try {
                val battery = DeviceUtils.getBatteryLevel(applicationContext)
                val storage = DeviceUtils.getFreeStorageGB()

                var lat: Double? = null
                var lon: Double? = null
                var accuracy: Float? = null
                var provider: String? = null
                var locTs: Long? = null

                if (prefs.locationEnabled) {
                    val locationHelper = LocationHelper(applicationContext)
                    val location = locationHelper.getCurrentLocation()
                    location?.let {
                        lat = it.latitude
                        lon = it.longitude
                        accuracy = it.accuracy
                        provider = it.provider
                        locTs = it.time
                    }
                }

                val request = PingRequest(
                    deviceUuid = prefs.deviceUuid,
                    battery = battery.takeIf { it >= 0 },
                    freeStorage = storage.takeIf { it >= 0 },
                    note = null,
                    lat = lat,
                    lon = lon,
                    accuracy = accuracy,
                    provider = provider,
                    locTs = locTs
                )

                val apiService = RetrofitClient.getApiService(prefs.serverUrl)
                val response = withContext(Dispatchers.IO) { apiService.ping(request) }

                withContext(Dispatchers.Main) {
                    if (response.isSuccessful && response.body()?.success == true) {
                        Toast.makeText(this@MainActivity, "✓ Update sent successfully", Toast.LENGTH_SHORT).show()
                    } else {
                        val msg = response.body()?.error ?: "Server error (${response.code()})"
                        Toast.makeText(this@MainActivity, "✗ Failed: $msg", Toast.LENGTH_LONG).show()
                    }
                    button.isEnabled = true
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    Toast.makeText(this@MainActivity, "✗ Error: ${e.message}", Toast.LENGTH_LONG).show()
                    button.isEnabled = true
                }
            }
        }
    }
    
    override fun onResume() {
        super.onResume()
        setupUI() // Refresh UI in case settings changed
    }
}
