import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Alert, Box, Button, Checkbox, Divider, FormControlLabel, IconButton, InputAdornment, Stack, TextField, Typography } from '@mui/material';
import AlternateEmailRounded from '@mui/icons-material/AlternateEmailRounded';
import LockOutlined from '@mui/icons-material/LockOutlined';
import VisibilityOutlined from '@mui/icons-material/VisibilityOutlined';
import VisibilityOffOutlined from '@mui/icons-material/VisibilityOffOutlined';
import ArrowForwardRounded from '@mui/icons-material/ArrowForwardRounded';
import ShieldOutlined from '@mui/icons-material/ShieldOutlined';
import { FormEventHandler, useState } from 'react';

export default function Login({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ email: '', password: '', remember: false });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title="School portal login">
                <meta name="description" content="Secure portal access for the Shobuj Ridge School & College community." />
            </Head>
            <Box sx={{ mb: 5 }}>
                <Typography variant="overline" color="secondary.dark" fontWeight={850} letterSpacing={1.7}>WELCOME BACK</Typography>
                <Typography variant="h2" sx={{ fontSize: { xs: 42, sm: 52 }, mt: 1 }}>Sign in to your portal.</Typography>
                <Typography color="text.secondary" fontSize={17} mt={2}>Use the email address provided by the school. Your role will take you to the right workspace automatically.</Typography>
            </Box>
            {status && <Alert severity="success" sx={{ mb: 3 }}>{status}</Alert>}
            <Box component="form" onSubmit={submit} noValidate>
                <Stack spacing={2.5}>
                    <TextField id="email" label="School email address" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} error={Boolean(errors.email)} helperText={errors.email || 'Example: name@shobujridge.edu.bd'} autoComplete="username" autoFocus fullWidth InputProps={{ startAdornment: <InputAdornment position="start"><AlternateEmailRounded color="action" /></InputAdornment> }} sx={{ '& .MuiInputBase-root': { minHeight: 58, bgcolor: 'white' } }} />
                    <TextField id="password" label="Password" type={showPassword ? 'text' : 'password'} value={data.password} onChange={(event) => setData('password', event.target.value)} error={Boolean(errors.password)} helperText={errors.password} autoComplete="current-password" fullWidth InputProps={{ startAdornment: <InputAdornment position="start"><LockOutlined color="action" /></InputAdornment>, endAdornment: <InputAdornment position="end"><IconButton onClick={() => setShowPassword((value) => !value)} edge="end" aria-label={showPassword ? 'Hide password' : 'Show password'}>{showPassword ? <VisibilityOffOutlined /> : <VisibilityOutlined />}</IconButton></InputAdornment> }} sx={{ '& .MuiInputBase-root': { minHeight: 58, bgcolor: 'white' } }} />
                    <Stack direction="row" alignItems="center" justifyContent="space-between" flexWrap="wrap" gap={1}>
                        <FormControlLabel control={<Checkbox checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} color="primary" />} label={<Typography variant="body2">Keep me signed in</Typography>} />
                        {canResetPassword && <Link href={route('password.request')} style={{ color: '#0b4d3b', fontWeight: 700, fontSize: 14 }}>Forgot password?</Link>}
                    </Stack>
                    <Button type="submit" variant="contained" size="large" disabled={processing} endIcon={!processing && <ArrowForwardRounded />} sx={{ minHeight: 54, fontSize: 16 }}>{processing ? 'Signing you in…' : 'Sign in securely'}</Button>
                </Stack>
            </Box>
            <Divider sx={{ my: 4 }} />
            <Stack direction="row" spacing={1.5} alignItems="flex-start">
                <ShieldOutlined color="primary" sx={{ mt: .2 }} />
                <Box><Typography fontWeight={750} fontSize={14}>Private school system</Typography><Typography variant="body2" color="text.secondary">Access is limited to approved Shobuj Ridge accounts. Contact the school office if you need an account or your role has changed.</Typography></Box>
            </Stack>
        </GuestLayout>
    );
}
