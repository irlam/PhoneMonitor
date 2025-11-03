package com.example.phonemonitor

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity

class ConsentActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Check if consent already given
        if (DeviceIdManager.hasConsentGiven(this)) {
            startMainActivity()
            return
        }

        setContentView(R.layout.activity_consent)

        val acceptButton: Button = findViewById(R.id.acceptButton)
        val declineButton: Button = findViewById(R.id.declineButton)

        acceptButton.setOnClickListener {
            DeviceIdManager.setConsentGiven(this, true)
            Toast.makeText(this, "Thank you for your consent", Toast.LENGTH_SHORT).show()
            startMainActivity()
        }

        declineButton.setOnClickListener {
            Toast.makeText(
                this,
                "You must accept to use this app. The app will now close.",
                Toast.LENGTH_LONG
            ).show()
            finish()
        }
    }

    private fun startMainActivity() {
        val intent = Intent(this, MainActivity::class.java)
        startActivity(intent)
        finish()
    }
}
