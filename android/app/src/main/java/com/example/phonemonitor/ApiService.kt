package com.example.phonemonitor

import com.google.gson.annotations.SerializedName
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.POST

data class RegisterRequest(
    @SerializedName("device_uuid") val deviceUuid: String,
    @SerializedName("display_name") val displayName: String,
    @SerializedName("owner_name") val ownerName: String,
    @SerializedName("consent") val consent: Boolean
)

data class PingRequest(
    @SerializedName("device_uuid") val deviceUuid: String,
    @SerializedName("battery") val battery: Int?,
    @SerializedName("free_storage") val freeStorage: String?,
    @SerializedName("note") val note: String?,
    @SerializedName("lat") val lat: Double?,
    @SerializedName("lon") val lon: Double?,
    @SerializedName("accuracy") val accuracy: Float?,
    @SerializedName("provider") val provider: String?,
    @SerializedName("loc_ts") val locTs: Long?
)

data class UnregisterRequest(
    @SerializedName("device_uuid") val deviceUuid: String
)

data class ApiResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?,
    @SerializedName("error") val error: String?
)

interface ApiService {
    @POST("api/register.php")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse>

    @POST("api/ping.php")
    suspend fun ping(@Body request: PingRequest): Response<ApiResponse>

    @POST("api/unregister.php")
    suspend fun unregister(@Body request: UnregisterRequest): Response<ApiResponse>

    companion object {
        fun create(baseUrl: String): ApiService {
            val okHttpClient = okhttp3.OkHttpClient.Builder()
                .connectTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                .readTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                .writeTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                .build()
            
            val retrofit = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(okHttpClient)
                .addConverterFactory(GsonConverterFactory.create())
                .build()

            return retrofit.create(ApiService::class.java)
        }
    }
}
