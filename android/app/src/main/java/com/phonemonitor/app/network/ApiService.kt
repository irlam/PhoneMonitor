package com.phonemonitor.app.network

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.POST

interface ApiService {
    
    @POST("api/register.php")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse>
    
    @POST("api/ping.php")
    suspend fun ping(@Body request: PingRequest): Response<ApiResponse>
    
    @POST("api/unregister.php")
    suspend fun unregister(@Body request: UnregisterRequest): Response<ApiResponse>
}
