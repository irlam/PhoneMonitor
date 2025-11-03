package com.phonemonitor.app.ui

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.widget.Button
import android.widget.Switch
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.phonemonitor.app.R
import com.phonemonitor.app.data.DevicePreferences
import com.phonemonitor.app.network.UnregisterRequest
import com.phonemonitor.app.network.RetrofitClient
import com.phonemonitor.app.utils.LocationHelper
import com.phonemonitor.app.worker.HeartbeatWorker
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class SettingsActivity : AppCompatActivity() {
    
    private lateinit var prefs: DevicePreferences
    private lateinit var locationHelper: LocationHelper
    
    private val locationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val fineLocation = permissions[Manifest.permission.ACCESS_FINE_LOCATION] ?: false
        val coarseLocation = permissions[Manifest.permission.ACCESS_COARSE_LOCATION] ?: false
        
        if (fineLocation || coarseLocation) {
            prefs.locationEnabled = true
            findViewById<Switch>(R.id.locationSwitch).isChecked = true
            Toast.makeText(this, "Location sharing enabled", Toast.LENGTH_SHORT).show()
        } else {
            prefs.locationEnabled = false
            findViewById<Switch>(R.id.locationSwitch).isChecked = false
            Toast.makeText(this, "Location permission denied", Toast.LENGTH_SHORT).show()
        }
    }
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_settings)
        
        prefs = DevicePreferences(this)
        locationHelper = LocationHelper(this)
        
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        
        setupUI()
    }
    
    private fun setupUI() {
        val serverUrlText = findViewById<TextView>(R.id.serverUrlText)
        val locationSwitch = findViewById<Switch>(R.id.locationSwitch)
        val locationInfoText = findViewById<TextView>(R.id.locationInfoText)
        val unregisterButton = findViewById<Button>(R.id.unregisterButton)
        
        serverUrlText.text = prefs.serverUrl
        
        locationSwitch.isChecked = prefs.locationEnabled
        locationInfoText.text = getString(R.string.location_info)
        
        locationSwitch.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) {
                if (locationHelper.hasLocationPermission()) {
                    prefs.locationEnabled = true
                } else {
                    // Request permission
                    requestLocationPermission()
                }
            } else {
                prefs.locationEnabled = false
            }
        }
        
        unregisterButton.setOnClickListener {
            showUnregisterConfirmation()
        }
    }
    
    private fun requestLocationPermission() {
        val permissions = mutableListOf(
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION
        )
        
        locationPermissionLauncher.launch(permissions.toTypedArray())
    }
    
    private fun showUnregisterConfirmation() {
        AlertDialog.Builder(this)
            .setTitle("Unregister Device")
            .setMessage("Are you sure you want to stop sharing this device? This will remove it from the dashboard.")
            .setPositiveButton("Unregister") { _, _ ->
                unregisterDevice()
            }
            .setNegativeButton("Cancel", null)
            .show()
    }
    
    private fun unregisterDevice() {
        lifecycleScope.launch {
            try {
                val request = UnregisterRequest(prefs.deviceUuid)
                val apiService = RetrofitClient.getApiService(prefs.serverUrl)
                val response = withContext(Dispatchers.IO) {
                    apiService.unregister(request)
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    // Cancel worker
                    HeartbeatWorker.cancel(this@SettingsActivity)
                    
                    // Clear preferences
                    prefs.clear()
                    
                    Toast.makeText(
                        this@SettingsActivity,
                        "Device unregistered successfully",
                        Toast.LENGTH_SHORT
                    ).show()
                    
                    // Go back to consent activity
                    val intent = Intent(this@SettingsActivity, ConsentActivity::class.java)
                    intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                    startActivity(intent)
                    finish()
                } else {
                    Toast.makeText(
                        this@SettingsActivity,
                        "Failed to unregister: ${response.body()?.error}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(
                    this@SettingsActivity,
                    "Network error: ${e.message}",
                    Toast.LENGTH_LONG
                ).show()
            }
        }
    }
    
    override fun onSupportNavigateUp(): Boolean {
        finish()
        return true
    }
}
