import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Box, Button, Card, MenuItem, Stack, TextField, Typography } from '@mui/material';
import { FormEvent } from 'react';

type Role = { id:number; name:string; slug:string };
type ManagedUser = { id:number; name:string; email:string; role_id:number; role?:Role };

export default function Form({ user, roles }: { user?:ManagedUser; roles:Role[] }) {
    const form = useForm({ name:user?.name || '', email:user?.email || '', role_id:String(user?.role_id || roles[0]?.id || ''), password:'', password_confirmation:'' });
    const submit = (event:FormEvent) => { event.preventDefault(); user ? form.put(`/admin/users/${user.id}`) : form.post('/admin/users'); };
    return <AdminLayout><Head title={user ? `Edit ${user.name}` : 'Add user'} />
        <Button component={Link} href="/admin/users" color="inherit" startIcon={<ArrowBackRounded />} sx={{mb:3}}>All users</Button>
        <Box mb={4}><Typography variant="h3" fontFamily="Newsreader">{user ? 'Edit user account' : 'Create user account'}</Typography><Typography color="text.secondary">{user ? 'Update the person’s account details and portal access.' : 'Create a secure account and assign the right portal.'}</Typography></Box>
        <Card component="form" onSubmit={submit} sx={{p:{xs:3,md:5},maxWidth:760}}><Stack spacing={2.5}><TextField label="Full name" value={form.data.name} onChange={event => form.setData('name', event.target.value)} error={!!form.errors.name} helperText={form.errors.name} autoComplete="name" /><TextField label="Email address" type="email" value={form.data.email} onChange={event => form.setData('email', event.target.value)} error={!!form.errors.email} helperText={form.errors.email} autoComplete="email" /><TextField select label="Role / portal access" value={form.data.role_id} onChange={event => form.setData('role_id', event.target.value)} error={!!form.errors.role_id} helperText={form.errors.role_id}>{roles.map(role => <MenuItem key={role.id} value={String(role.id)}>{role.name}</MenuItem>)}</TextField><TextField label={user ? 'New password (optional)' : 'Temporary password'} type="password" value={form.data.password} onChange={event => form.setData('password', event.target.value)} error={!!form.errors.password} helperText={form.errors.password || (user ? 'Leave blank to keep the current password.' : 'At least 8 characters. Share this securely with the user.')} autoComplete="new-password" /><TextField label="Confirm password" type="password" value={form.data.password_confirmation} onChange={event => form.setData('password_confirmation', event.target.value)} error={!!form.errors.password_confirmation} helperText={form.errors.password_confirmation} autoComplete="new-password" /><Stack direction="row" spacing={2}><Button type="submit" variant="contained" startIcon={<SaveOutlined />} disabled={form.processing}>{form.processing ? 'Saving…' : 'Save account'}</Button><Button component={Link} href="/admin/users" color="inherit">Cancel</Button></Stack></Stack></Card>
    </AdminLayout>;
}
