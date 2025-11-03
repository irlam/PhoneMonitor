package com.phonemonitor.app.ui

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.CheckBox
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.phonemonitor.app.R
import com.phonemonitor.app.data.DevicePreferences
import com.phonemonitor.app.network.RegisterRequest
import com.phonemonitor.app.network.RetrofitClient
import com.phonemonitor.app.worker.HeartbeatWorker
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class ConsentActivity : AppCompatActivity() {
    
    private lateinit var prefs: DevicePreferences
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        prefs = DevicePreferences(this)
        
        // If already consented, go to main activity
        if (prefs.consentGiven) {
            startMainActivity()
            return
        }
        
        setContentView(R.layout.activity_consent)
        
        setupUI()
    }
    
    private fun setupUI() {
        val consentText = findViewById<TextView>(R.id.consentText)
        val serverUrlInput = findViewById<EditText>(R.id.serverUrlInput)
        val displayNameInput = findViewById<EditText>(R.id.displayNameInput)
        val ownerNameInput = findViewById<EditText>(R.id.ownerNameInput)
        val consentCheckbox = findViewById<CheckBox>(R.id.consentCheckbox)
        val acceptButton = findViewById<Button>(R.id.acceptButton)
        val declineButton = findViewById<Button>(R.id.declineButton)
        
        consentText.text = getString(R.string.consent_message)
        
        // Pre-fill with saved values if any
        serverUrlInput.setText(prefs.serverUrl)
        displayNameInput.setText(prefs.displayName)
        ownerNameInput.setText(prefs.ownerName)
        
        acceptButton.setOnClickListener {
            if (!consentCheckbox.isChecked) {
                Toast.makeText(this, "Please read and accept the consent", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            
            val serverUrl = serverUrlInput.text.toString().trim()
            val displayName = displayNameInput.text.toString().trim()
            val ownerName = ownerNameInput.text.toString().trim()
            
            if (serverUrl.isEmpty() || displayName.isEmpty() || ownerName.isEmpty()) {
                Toast.makeText(this, "Please fill all fields", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            
            // Save preferences
            prefs.serverUrl = serverUrl
            prefs.displayName = displayName
            prefs.ownerName = ownerName
            prefs.consentGiven = true
            
            // Register device
            registerDevice()
        }
        
        declineButton.setOnClickListener {
            finish()
        }
    }
    
    private fun registerDevice() {
        lifecycleScope.launch {
            try {
                val request = RegisterRequest(
                    deviceUuid = prefs.deviceUuid,
                    displayName = prefs.displayName,
                    ownerName = prefs.ownerName,
                    consent = true
                )
                
                val apiService = RetrofitClient.getApiService(prefs.serverUrl)
                val response = withContext(Dispatchers.IO) {
                    apiService.register(request)
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    // Schedule heartbeat worker
                    HeartbeatWorker.schedule(this@ConsentActivity, prefs.heartbeatInterval)
                    
                    Toast.makeText(
                        this@ConsentActivity,
                        "Device registered successfully",
                        Toast.LENGTH_SHORT
                    ).show()
                    
                    startMainActivity()
                } else {
                    val error = response.body()?.error ?: "Registration failed"
                    Toast.makeText(this@ConsentActivity, error, Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                e.printStackTrace()
                Toast.makeText(
                    this@ConsentActivity,
                    "Network error: ${e.message}",
                    Toast.LENGTH_LONG
                ).show()
            }
        }
    }
    
    private fun startMainActivity() {
        startActivity(Intent(this, MainActivity::class.java))
        finish()
    }
}
