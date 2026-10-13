import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import AddRounded from '@mui/icons-material/AddRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import { Box, Button, Card, Chip, Stack, Tab, Tabs, Typography } from '@mui/material';

type Year = {id:number;name:string;starts_on:string;ends_on:string;is_current:boolean};
type Section = {id:number;name:string;capacity?:number;school_class_id:number};
type SchoolClass = {id:number;name:string;code:string;sort_order:number;sections:Section[]};
type Subject = {id:number;name:string;code:string;sort_order:number};
type Props = {tab:string;years:Year[];classes:SchoolClass[];subjects:Subject[]};
const labels:Record<string,string>={years:'Academic years',classes:'Classes',sections:'Sections',subjects:'Subjects'};

export default function Index({tab,years,classes,subjects}:Props){
    const records = tab==='years' ? years : tab==='classes' ? classes : tab==='sections' ? classes.flatMap(item=>item.sections.map(section=>({...section,school_class:item}))) : subjects;
    const remove=(id:number,name:string)=>confirm(`Delete “${name}”?`)&&router.delete(`/admin/academics/${tab}/${id}`);
    return <AdminLayout><Head title="Academic setup"/><Stack direction={{xs:'column',sm:'row'}} justifyContent="space-between" spacing={2} mb={4}><Box><Typography variant="h3" fontFamily="Newsreader">Academic setup</Typography><Typography color="text.secondary">Set up the academic structure used by profiles, attendance, routines, and results.</Typography></Box><Button component={Link} href={`/admin/academics/${tab}/create`} variant="contained" startIcon={<AddRounded/>}>Add {labels[tab].slice(0,-1)}</Button></Stack>
        <Card sx={{p:{xs:2,md:3}}}><Tabs value={tab} onChange={(_,value)=>router.get('/admin/academics',{tab:value})} variant="scrollable" scrollButtons="auto" sx={{mb:3}}>{Object.entries(labels).map(([key,label])=><Tab key={key} value={key} label={label}/>)}</Tabs><Stack spacing={1.5}>{records.map((record:any)=><Card key={record.id} variant="outlined" sx={{p:2.5,boxShadow:'none'}}><Stack direction={{xs:'column',sm:'row'}} alignItems={{sm:'center'}} spacing={2}><Box flexGrow={1}>{tab==='years'&&<><Stack direction="row" spacing={1} alignItems="center"><Typography fontWeight={800}>{record.name}</Typography>{record.is_current&&<Chip size="small" label="Current" color="primary"/>}</Stack><Typography variant="body2" color="text.secondary">{new Date(record.starts_on).toLocaleDateString('en-BD',{dateStyle:'medium'})} – {new Date(record.ends_on).toLocaleDateString('en-BD',{dateStyle:'medium'})}</Typography></>}{tab==='classes'&&<><Typography fontWeight={800}>{record.name} <Typography component="span" color="text.secondary">· {record.code}</Typography></Typography><Typography variant="body2" color="text.secondary">{record.sections.length} section{record.sections.length===1?'':'s'} · Display order {record.sort_order}</Typography></>}{tab==='sections'&&<><Typography fontWeight={800}>{record.school_class.name} · Section {record.name}</Typography><Typography variant="body2" color="text.secondary">{record.capacity?`Capacity ${record.capacity}`:'No capacity set'}</Typography></>}{tab==='subjects'&&<><Typography fontWeight={800}>{record.name} <Typography component="span" color="text.secondary">· {record.code}</Typography></Typography><Typography variant="body2" color="text.secondary">Display order {record.sort_order}</Typography></>}</Box><Stack direction="row"><Button component={Link} href={`/admin/academics/${tab}/${record.id}/edit`} startIcon={<EditRounded/>}>Edit</Button><Button color="error" startIcon={<DeleteOutlineRounded/>} onClick={()=>remove(record.id,record.name)}>Delete</Button></Stack></Stack></Card>)}</Stack>{!records.length&&<Box textAlign="center" py={8}><Typography variant="h6">No {labels[tab].toLowerCase()} yet</Typography><Typography color="text.secondary">Add the first one to begin building the academic structure.</Typography></Box>}</Card>
    </AdminLayout>;
}
