package com.example.phonemonitor

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.work.*
import com.google.android.material.appbar.MaterialToolbar
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.util.concurrent.TimeUnit

class MainActivity : AppCompatActivity() {
    private val LOCATION_PERMISSION_CODE = 100
    private val NOTIFICATION_PERMISSION_CODE = 101

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val toolbar: MaterialToolbar = findViewById(R.id.toolbar)
        toolbar.title = getString(R.string.app_name)

        val statusText: TextView = findViewById(R.id.statusText)
        val deviceInfoText: TextView = findViewById(R.id.deviceInfoText)
        val settingsButton: Button = findViewById(R.id.settingsButton)
        val unregisterButton: Button = findViewById(R.id.unregisterButton)

        // Display device info
        val deviceUuid = DeviceIdManager.getDeviceUuid(this)
        val deviceName = DeviceIdManager.getDeviceName(this)
        val ownerName = DeviceIdManager.getOwnerName(this)

        deviceInfoText.text = """
            Device: $deviceName
            Owner: $ownerName
            UUID: ${deviceUuid.substring(0, 8)}...
        """.trimIndent()

        statusText.text = "Active - Sharing every 30 minutes"

        settingsButton.setOnClickListener {
            startActivity(Intent(this, SettingsActivity::class.java))
        }

        unregisterButton.setOnClickListener {
            showUnregisterDialog()
        }

        // Request notification permission (Android 13+)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) 
                != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(
                    this,
                    arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                    NOTIFICATION_PERMISSION_CODE
                )
            }
        }

        // Schedule heartbeat worker
        scheduleHeartbeatWorker()

        // Register device on first launch
        registerDevice()
    }

    private fun scheduleHeartbeatWorker() {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()

        val workRequest = PeriodicWorkRequestBuilder<HeartbeatWorker>(30, TimeUnit.MINUTES)
            .setConstraints(constraints)
            .setBackoffCriteria(BackoffPolicy.LINEAR, 15, TimeUnit.MINUTES)
            .build()

        WorkManager.getInstance(this).enqueueUniquePeriodicWork(
            "heartbeat_worker",
            ExistingPeriodicWorkPolicy.KEEP,
            workRequest
        )
    }

    private fun registerDevice() {
        CoroutineScope(Dispatchers.IO).launch {
            try {
                val apiService = ApiService.create(DeviceIdManager.getServerUrl(this@MainActivity))
                val response = apiService.register(
                    RegisterRequest(
                        deviceUuid = DeviceIdManager.getDeviceUuid(this@MainActivity),
                        displayName = DeviceIdManager.getDeviceName(this@MainActivity),
                        ownerName = DeviceIdManager.getOwnerName(this@MainActivity),
                        consent = true
                    )
                )

                withContext(Dispatchers.Main) {
                    if (response.isSuccessful && response.body()?.success == true) {
                        Toast.makeText(
                            this@MainActivity,
                            "Device registered successfully",
                            Toast.LENGTH_SHORT
                        ).show()
                    }
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    Toast.makeText(
                        this@MainActivity,
                        "Registration failed: ${e.message}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }
    }

    private fun showUnregisterDialog() {
        AlertDialog.Builder(this)
            .setTitle("Stop Sharing?")
            .setMessage("This will unregister your device and stop all sharing. You can reinstall the app later if needed.")
            .setPositiveButton("Yes, Stop Sharing") { _, _ ->
                unregisterDevice()
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun unregisterDevice() {
        CoroutineScope(Dispatchers.IO).launch {
            try {
                val apiService = ApiService.create(DeviceIdManager.getServerUrl(this@MainActivity))
                val response = apiService.unregister(
                    UnregisterRequest(
                        deviceUuid = DeviceIdManager.getDeviceUuid(this@MainActivity)
                    )
                )

                withContext(Dispatchers.Main) {
                    if (response.isSuccessful && response.body()?.success == true) {
                        // Cancel work
                        WorkManager.getInstance(this@MainActivity).cancelUniqueWork("heartbeat_worker")

                        // Clear consent
                        DeviceIdManager.setConsentGiven(this@MainActivity, false)

                        Toast.makeText(
                            this@MainActivity,
                            "Device unregistered. You can now uninstall the app.",
                            Toast.LENGTH_LONG
                        ).show()

                        // Restart to consent screen
                        val intent = Intent(this@MainActivity, ConsentActivity::class.java)
                        intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                        startActivity(intent)
                        finish()
                    }
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    Toast.makeText(
                        this@MainActivity,
                        "Unregistration failed: ${e.message}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }
    }
}
