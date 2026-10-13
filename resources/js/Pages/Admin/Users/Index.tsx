import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import AddRounded from '@mui/icons-material/AddRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import { Box, Button, Card, Chip, FormControl, IconButton, InputLabel, MenuItem, Pagination, Select, Stack, TextField, Typography } from '@mui/material';
import { FormEvent, useState } from 'react';

type Role = { id:number; name:string; slug:string };
type ManagedUser = { id:number; name:string; email:string; created_at:string; role?:Role };
type Props = { search:string; role?:string; roles:Role[]; users:{data:ManagedUser[];current_page:number;last_page:number} };
const access:Record<string, string> = { 'super-admin':'Full platform control', admin:'Administrative control', teacher:'Teacher portal access', guardian:'Guardian portal access', student:'Student portal access' };

export default function Index({ search, role, roles, users }: Props) {
    const [query, setQuery] = useState(search);
    const [selectedRole, setSelectedRole] = useState(role || '');
    const filters = (page = 1) => ({ search: query, role: selectedRole || undefined, page });
    const submit = (event:FormEvent) => { event.preventDefault(); router.get('/admin/users', filters(), { preserveState:true, replace:true }); };
    return <AdminLayout><Head title="Users & roles" />
        <Stack direction={{xs:'column',sm:'row'}} justifyContent="space-between" spacing={2} mb={4}><Box><Typography variant="h3" fontFamily="Newsreader">Users & roles</Typography><Typography color="text.secondary">Manage portal accounts and the access each person needs.</Typography></Box><Button component={Link} href="/admin/users/create" variant="contained" startIcon={<AddRounded />}>Add user</Button></Stack>
        <Box sx={{display:'grid',gridTemplateColumns:{xs:'1fr',md:`repeat(${Math.min(roles.length, 5)}, 1fr)`},gap:2,mb:3}}>{roles.map(item => <Card key={item.id} variant="outlined" sx={{p:2.25,boxShadow:'none'}}><Typography fontWeight={800}>{item.name}</Typography><Typography color="text.secondary" variant="caption">{access[item.slug] || 'Portal access'}</Typography></Card>)}</Box>
        <Card sx={{p:{xs:2,md:3}}}><Stack component="form" onSubmit={submit} direction={{xs:'column',md:'row'}} spacing={2} mb={3}><TextField fullWidth value={query} onChange={event => setQuery(event.target.value)} placeholder="Search name or email…" /><FormControl sx={{minWidth:{md:200}}}><InputLabel id="role-filter-label">Role</InputLabel><Select labelId="role-filter-label" label="Role" value={selectedRole} onChange={event => { setSelectedRole(event.target.value); router.get('/admin/users', {search:query,role:event.target.value || undefined}, {preserveState:true,replace:true}); }}><MenuItem value="">All roles</MenuItem>{roles.map(item => <MenuItem key={item.id} value={item.slug}>{item.name}</MenuItem>)}</Select></FormControl></Stack>
            {users.data.length ? <Stack divider={<Box borderBottom="1px solid #e7e7e7" />}>{users.data.map(user => <Stack key={user.id} direction={{xs:'column',sm:'row'}} alignItems={{sm:'center'}} spacing={2} py={2}><Box flexGrow={1}><Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap"><Typography fontWeight={800}>{user.name}</Typography><Chip size="small" label={user.role?.name || 'No role'} color={user.role?.slug === 'super-admin' ? 'secondary' : 'primary'} /></Stack><Typography variant="body2" color="text.secondary">{user.email}</Typography></Box><Typography variant="caption" color="text.secondary">Created {new Date(user.created_at).toLocaleDateString('en-BD', {day:'numeric',month:'short',year:'numeric'})}</Typography><Stack direction="row"><IconButton component={Link} href={`/admin/users/${user.id}/edit`} aria-label={`Edit ${user.name}`}><EditRounded /></IconButton><IconButton color="error" onClick={() => confirm(`Delete the account for ${user.name}?`) && router.delete(`/admin/users/${user.id}`, { preserveScroll:true })} aria-label={`Delete ${user.name}`}><DeleteOutlineRounded /></IconButton></Stack></Stack>)}</Stack> : <Box textAlign="center" py={9}><Typography variant="h6">No users found</Typography><Typography color="text.secondary" mt={1}>Change your filters or create a new account.</Typography></Box>}
            {users.last_page > 1 && <Pagination count={users.last_page} page={users.current_page} onChange={(_, page) => router.get('/admin/users', filters(page), {preserveState:true})} sx={{mt:3}} />}
        </Card>
    </AdminLayout>;
}
