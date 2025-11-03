package com.example.phonemonitor

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.widget.Button
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import com.google.android.material.appbar.MaterialToolbar
import com.google.android.material.switchmaterial.SwitchMaterial
import com.google.android.material.textfield.TextInputEditText

class SettingsActivity : AppCompatActivity() {
    private val LOCATION_PERMISSION_CODE = 100
    private val BACKGROUND_LOCATION_PERMISSION_CODE = 101

    private lateinit var locationSwitch: SwitchMaterial

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_settings)

        val toolbar: MaterialToolbar = findViewById(R.id.toolbar)
        setSupportActionBar(toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)

        val serverUrlInput: TextInputEditText = findViewById(R.id.serverUrlInput)
        val deviceNameInput: TextInputEditText = findViewById(R.id.deviceNameInput)
        val ownerNameInput: TextInputEditText = findViewById(R.id.ownerNameInput)
        locationSwitch = findViewById(R.id.locationSwitch)
        val saveButton: Button = findViewById(R.id.saveButton)

        // Load current settings
        serverUrlInput.setText(DeviceIdManager.getServerUrl(this))
        deviceNameInput.setText(DeviceIdManager.getDeviceName(this))
        ownerNameInput.setText(DeviceIdManager.getOwnerName(this))
        locationSwitch.isChecked = DeviceIdManager.isLocationEnabled(this)

        locationSwitch.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) {
                requestLocationPermission()
            } else {
                DeviceIdManager.setLocationEnabled(this, false)
            }
        }

        saveButton.setOnClickListener {
            val serverUrl = serverUrlInput.text.toString().trim()
            val deviceName = deviceNameInput.text.toString().trim()
            val ownerName = ownerNameInput.text.toString().trim()

            if (serverUrl.isEmpty() || deviceName.isEmpty() || ownerName.isEmpty()) {
                Toast.makeText(this, "All fields are required", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            DeviceIdManager.setServerUrl(this, serverUrl)
            DeviceIdManager.setDeviceName(this, deviceName)
            DeviceIdManager.setOwnerName(this, ownerName)

            Toast.makeText(this, "Settings saved", Toast.LENGTH_SHORT).show()
            finish()
        }
    }

    private fun requestLocationPermission() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION)
            != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(
                this,
                arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,
                    Manifest.permission.ACCESS_COARSE_LOCATION
                ),
                LOCATION_PERMISSION_CODE
            )
        } else {
            requestBackgroundLocationPermission()
        }
    }

    private fun requestBackgroundLocationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_BACKGROUND_LOCATION)
                != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(
                    this,
                    arrayOf(Manifest.permission.ACCESS_BACKGROUND_LOCATION),
                    BACKGROUND_LOCATION_PERMISSION_CODE
                )
            } else {
                DeviceIdManager.setLocationEnabled(this, true)
            }
        } else {
            DeviceIdManager.setLocationEnabled(this, true)
        }
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)

        when (requestCode) {
            LOCATION_PERMISSION_CODE -> {
                if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                    requestBackgroundLocationPermission()
                } else {
                    locationSwitch.isChecked = false
                    Toast.makeText(
                        this,
                        getString(R.string.location_permission_rationale),
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
            BACKGROUND_LOCATION_PERMISSION_CODE -> {
                if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                    DeviceIdManager.setLocationEnabled(this, true)
                    Toast.makeText(this, "Location sharing enabled", Toast.LENGTH_SHORT).show()
                } else {
                    locationSwitch.isChecked = false
                    Toast.makeText(
                        this,
                        "Background location permission is required for location sharing",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        finish()
        return true
    }
}
