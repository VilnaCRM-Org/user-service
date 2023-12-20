import { AppBar, Toolbar } from '@mui/material';

import VilnaMainIcon from '../Icons/VilnaMainIcon/VilnaMainIcon';

import Buttons from './Buttons/Buttons';
import NavLink from './NavLink/NavLink';

const links = [
  { id: 1, value: 'Переваги' },
  { id: 2, value: 'Для кого' },
  { id: 3, value: 'Інтеграція' },
  { id: 4, value: 'Контакти' },
];

function Header() {
  return (
    <AppBar
      position="static"
      sx={{ backgroundColor: 'white', boxShadow: 'none' }}
    >
      <Toolbar sx={{ p: 0, justifyContent: 'space-between' }}>
        <VilnaMainIcon />
        <NavLink links={links} />
        <Buttons />
      </Toolbar>
    </AppBar>
  );
}

export default Header;
