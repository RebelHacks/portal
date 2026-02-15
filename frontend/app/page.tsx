"use client";

import { useState, useRef } from "react";
import { useRouter } from "next/navigation";
import styles from "./login.module.css";
import api from "@/lib/api";
import type {ErrorResponse, LoginRequest, LoginResponse} from "@/lib/types";

export default function LoginPage() {
  const [mode, setMode] = useState<"login" | "register">("login");
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [username, setUsername] = useState('');
  const confirmRef = useRef<HTMLInputElement | null>(null);
  const passwordRef = useRef<HTMLInputElement | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();
  const [isJudge, setIsJudge] = useState<boolean>(false);
  const [showTooltip, setShowTooltip] = useState(false);
  const [major, setMajor] = useState("");
  const [track, setTrack] = useState("Software");
  const [transcript, setTranscript] = useState<File | null>(null);

  const handleLogin = async (redirect = true) => {
    try {
      const loginData: LoginRequest = {
        email: email.trim(),
        password: password
      };

      const response = await api.post<LoginResponse>('/login', loginData);

      // Store the JWT token
      if (response.token) {
        localStorage.setItem('authToken', response.token);
        if (response.refresh_token) {
          localStorage.setItem('refreshToken', response.refresh_token);
        }
        // Redirect to dashboard if requested
        if (redirect) router.push('/dashboard');
      } else {
        setError('Login failed: No token received');
      }
    } catch (err: any) {
      console.error('Login error:', err);
      
      // Handle different error types
      if (err.response?.status === 401) {
        setError('Invalid email or password');
      } else if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else if (err.message) {
        setError(err.message);
      } else {
        setError('An error occurred during login. Please try again.');
      }
    }
  };

  const handleRegister = async () => {
    try {
      const registerData = {
        email: email.trim(),
        password: password,
        confirmPassword: confirmPassword,
        username: username,
        agreeTerms: true
      };

      // First register the user
      await api.post('/register', registerData);
      // After registration, log in first so subsequent profile/file endpoints are authenticated
      await handleLogin(false);

      // If this is a non-judge user, update their profile and upload transcript
      if (!isJudge) {
        await api.patch('/users/profile', { major, track });
        if (transcript) {
          const fileData = new FormData();
          fileData.append('file', transcript);
          fileData.append('type', 'transcript');
          await api.post('/users/upload-file', fileData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            withCredentials: true,
          });
        }
      }

      // Finally, redirect to dashboard
      router.push('/dashboard');
} catch (err: any) {
  console.error('Registration error:', err);
  
  // Check for 'error' (Symfony) or 'message' (Generic)
  const apiError = err.response?.data?.error || err.response?.data?.message;

  if (apiError) {
    setError(apiError);
  } else if (err.message) {
    setError(err.message);
  } else {
    setError('An error occurred during registration. Please try again.');
  }
}
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    if (mode === "register") {
      // Password complexity: min 8 chars, lower, upper, number, special
      const complexRx = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
      if (!complexRx.test(password)) {
        if (passwordRef.current) {
          passwordRef.current.setCustomValidity('Password must be at least 8 characters and include lowercase, uppercase, number, and special character');
          passwordRef.current.reportValidity();
        } else {
          setError('Password must be at least 8 characters and include lowercase, uppercase, number, and special character');
        }
        setLoading(false);
        return;
      }

      if (password !== confirmPassword) {
        // show native validation popup on the confirm password input when available
        if (confirmRef.current) {
          confirmRef.current.setCustomValidity('Passwords do not match');
          confirmRef.current.reportValidity();
        } else {
          setError('Passwords do not match');
        }
        setLoading(false);
        return;
      }

      // clear any previous custom validity
      if (confirmRef.current) confirmRef.current.setCustomValidity('');
      if (passwordRef.current) passwordRef.current.setCustomValidity('');
      // Ensure profile fields for non-judge registrations
      if (!isJudge) {
        if (!major || track === 'Choose' || !transcript) {
          setError('Please provide your major, select a track, and upload your transcript (unless you are a judge).');
          setLoading(false);
          return;
        }
      }
    }

    try {
      if (mode === "login") {
        await handleLogin();
      } else {
        await handleRegister();
      }
    } finally {
      setLoading(false);
    }
  };

  const confirmPasswordsMatch = mode === "register" && password !== confirmPassword && confirmPassword.length > 0;

  return (
    <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
      <div className={styles.portalCard}>        
        {/* Header */}
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold">
            {mode === "login" ? "Welcome back" : "Create an account"}
          </h1>
          <p className={`${styles.mutedText} mt-2 text-sm`}>
            {mode === "login"
              ? "Sign in to access the portal"
              : "Register with your email and password"}
          </p>
        </div>

        {/* Error Message */}
        {error && (
          <div className="mb-4 p-3 rounded-md bg-red-500/10 border border-red-500 text-red-500 text-sm">
            {error}
          </div>
        )}

        {/* Form */}
        
        <form onSubmit={handleSubmit} className="w-full flex flex-col gap-4">
          {mode === "login" && (
            <>
              <label className="flex flex-col text-md">
                Email
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  disabled={loading}
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
                  placeholder="Email"
                />
              </label>
              
              <label className="flex flex-col text-md">
                Password
                <input
                  ref={passwordRef}
                  type="password"
                  required
                  value={password}
                  onChange={(e) => {
                    setPassword(e.target.value);
                    if (passwordRef.current) passwordRef.current.setCustomValidity('');
                  }}
                  disabled={loading}
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
                  placeholder="Password"
                />
              </label>
          </>
          )}
          
          {mode === "register" && (
            <div className="w-full flex flex-col gap-4">
              <label className="flex flex-col text-md">
                Name
                <input
                  type="text"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  disabled={loading}
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
                  placeholder="John Doe"
                />
              </label>
              <label className="flex flex-col text-md">
                Email
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  disabled={loading}
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
                  placeholder="Email"
                />
              </label>
              <label className="flex flex-col text-md">
                Password
                <input
                  ref={passwordRef}
                  type="password"
                  required
                  value={password}
                  onChange={(e) => {
                    setPassword(e.target.value);
                    if (passwordRef.current) passwordRef.current.setCustomValidity('');
                  }}
                  disabled={loading}
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
                  placeholder="Password"
                />
              </label>
              <label className="flex flex-col text-md">
                Confirm Password
                <input
                  ref={confirmRef}
                  type="password"
                  required
                  value={confirmPassword}
                  onChange={(e) => {
                    setConfirmPassword(e.target.value);
                    if (confirmRef.current) confirmRef.current.setCustomValidity('');
                  }}
                  disabled={loading}
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
                  placeholder="Confirm Password"
                />
              </label>
              <div className="flex flex-col gap-3">
                <span className="text-md font-medium">Are you a judge?</span>
                <div className="flex flex-row gap-4">
                  {['Yes', 'No'].map((option) => (
                    <label key={option} className="flex items-center gap-2 cursor-pointer group">
                      <input
                        type="radio"
                        name="isJudge"
                        value={option}
                        checked={isJudge === (option === 'Yes')}
                        onChange={() => setIsJudge(option === 'Yes')}
                        className="w-4 h-4 accent-cyan-400"
                      />
                      <span className="group-hover:text-cyan-400 transition-colors">{option}</span>
                    </label>
                  ))}
                </div>

                {isJudge ? (
                  <div className="bg-slate-800/50 p-4 rounded-lg border border-slate-600 mb-4">
                    <h3 className="text-lg font-semibold text-cyan-400 mb-3">Judge Registration Process</h3>
                    <ul className="text-base text-gray-200 space-y-3">
                      <li className="flex items-start gap-3">
                        <span className="text-cyan-400 text-lg mt-0.5">•</span>
                        <span>Click the register button below to submit your judge application.</span>
                      </li>
                    </ul>
                  </div>
                ) : (
                  <div className="flex flex-col gap-4">
                    <div className="flex flex-col text-md">
                      <label className="mb-2">What is your major?</label>
                      <select
                        required={!isJudge}
                        value={track}
                        onChange={(e) => setTrack(e.target.value)}
                        disabled={loading}
                        className={`${styles.input} w-full px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50`}
                      >
                        <option value="Choose">Choose</option>
                        <option value="Computer Science ">Computer Science</option>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Cybersecurity">Cybersecurity</option> 
                        <option value="Civil Engineering">Civil Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Entertainment Engineering">Entertainment Engineering</option>
                        <option value="Information Systems">Information Systems</option>
                        <option value="Data Analytics">Data Analytics</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Economics">Economics</option>
                      </select>
                    </div>

                    <div className="flex flex-col text-md">
                      <label className="mb-2">Double major?</label>
                      <select
                        required={!isJudge}
                        value={track}
                        onChange={(e) => setTrack(e.target.value)}
                        disabled={loading}
                        className={`${styles.input} w-full px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50`}
                      >
                        <option value="Choose">Choose</option>
                        <option value="Choose">N/A</option>
                        <option value="Computer Science ">Computer Science</option>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Cybersecurity">Cybersecurity</option> 
                        <option value="Civil Engineering">Civil Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Entertainment Engineering">Entertainment Engineering</option>
                        <option value="Information Systems">Information Systems</option>
                        <option value="Data Analytics">Data Analytics</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Economics">Economics</option>
                      </select>
                    </div>

                  </div>
                )}
              </div>
            </div>
            )}

          <button
            type="submit"
            disabled={loading}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed transition-opacity`}
          >
            {loading ? (mode === "login" ? "Logging in..." : "Registering...") : (mode === "login" ? "Log In" : "Register")}
          </button>
        </form>

        {/* Actions */}
        <div className="mt-4 flex flex-col items-center gap-2 text-sm">
          {mode === "login" && (
            <button
              onClick={() => {
                const backendOrigin = (process.env.NEXT_PUBLIC_BACKEND_URL as string) ?? `${window.location.protocol}//${window.location.hostname}:8000`;
                window.location.assign(`${backendOrigin}/reset-password`);
              }}
              className={`${styles.mutedText} hover:underline`}
            >
              Forgot password?
            </button>
          )}
          <button
            onClick={() => setMode(mode === "login" ? "register" : "login")}
            className={`${styles.mutedText} hover:underline`}
          >
            {mode === "login"
              ? "Don’t have an account? Register"
              : "Already have an account? Log in"}
          </button>
        </div>
      </div>
    </div>
  );
}