"use client";

import { useState, useEffect, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import styles from "../../login.module.css";
import api from "@/lib/api";

function ResetPasswordConfirmContent() {
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [validating, setValidating] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams.get('token');

  useEffect(() => {
    const validateToken = async () => {
      if (!token) {
        setError('Invalid reset link. Please request a new password reset.');
        setValidating(false);
        return;
      }

      try {
        await api.post('/reset-password/validate', { token });
        setValidating(false);
      } catch (err: any) {
        console.error('Token validation error:', err);
        setError(err.response?.data?.message || 'Invalid or expired reset token.');
        setValidating(false);
      }
    };

    validateToken();
  }, [token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    // Password complexity validation
    const complexRx = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
    if (!complexRx.test(password)) {
      setError('Password must be at least 8 characters and include lowercase, uppercase, number, and special character');
      setLoading(false);
      return;
    }

    if (password !== confirmPassword) {
      setError('Passwords do not match');
      setLoading(false);
      return;
    }

    try {
      await api.post('/reset-password/reset', { token, password });
      setSuccess(true);
    } catch (err: any) {
      console.error('Reset password error:', err);
      if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else if (err.message) {
        setError(err.message);
      } else {
        setError('An error occurred. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  if (validating) {
    return (
      <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
        <div className={styles.portalCard}>
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-400 mx-auto"></div>
            <p className="mt-4 text-cyan-400">Validating reset link...</p>
          </div>
        </div>
      </div>
    );
  }

  if (success) {
    return (
      <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
        <div className={styles.portalCard}>
          <div className="text-center mb-6">
            <h1 className="text-2xl font-bold text-green-400">Password Reset!</h1>
            <p className={`${styles.mutedText} mt-2 text-sm`}>
              Your password has been reset successfully. You can now log in with your new password.
            </p>
          </div>
          <button
            onClick={() => router.push('/')}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white transition-opacity`}
          >
            Go to Login
          </button>
        </div>
      </div>
    );
  }

  if (error && !token) {
    return (
      <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
        <div className={styles.portalCard}>
          <div className="text-center mb-6">
            <h1 className="text-2xl font-bold text-red-400">Invalid Link</h1>
            <p className={`${styles.mutedText} mt-2 text-sm`}>
              {error}
            </p>
          </div>
          <button
            onClick={() => router.push('/reset-password')}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white transition-opacity`}
          >
            Request New Reset Link
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
      <div className={styles.portalCard}>
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold">Set New Password</h1>
          <p className={`${styles.mutedText} mt-2 text-sm`}>
            Enter your new password below.
          </p>
        </div>

        {error && (
          <div className="mb-4 p-3 rounded-md bg-red-500/10 border border-red-500 text-red-500 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="w-full flex flex-col gap-4">
          <label className="flex flex-col text-md">
            New Password
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              disabled={loading}
              className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
              placeholder="Enter new password"
            />
          </label>

          <label className="flex flex-col text-md">
            Confirm Password
            <input
              type="password"
              required
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              disabled={loading}
              className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
              placeholder="Confirm new password"
            />
          </label>

          <p className="text-xs text-gray-400 -mt-2">
            Password must be at least 8 characters with lowercase, uppercase, number, and special character.
          </p>

          <button
            type="submit"
            disabled={loading}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed transition-opacity`}
          >
            {loading ? "Resetting..." : "Reset Password"}
          </button>
        </form>

        <div className="mt-4 flex flex-col items-center gap-2 text-sm">
          <button
            onClick={() => router.push('/')}
            className={`${styles.mutedText} hover:underline`}
          >
            Back to Login
          </button>
        </div>
      </div>
    </div>
  );
}

export default function ResetPasswordConfirmPage() {
  return (
    <Suspense fallback={
      <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
        <div className={styles.portalCard}>
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-400 mx-auto"></div>
            <p className="mt-4 text-cyan-400">Loading...</p>
          </div>
        </div>
      </div>
    }>
      <ResetPasswordConfirmContent />
    </Suspense>
  );
}