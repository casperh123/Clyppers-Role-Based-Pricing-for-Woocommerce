import { HashRouter, Routes, Route } from 'react-router-dom';
import { Roles } from './pages/roles';
import { Rules } from './pages/rules';

export default function App() {
  return (
    <HashRouter>
      <Routes>
        <Route path="/" element={<Roles />}/>
        <Route path="/role/:id" element={<Rules />}/>
      </Routes>
    </HashRouter>
  )
}
