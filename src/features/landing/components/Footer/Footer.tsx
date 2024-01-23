import Adaptive from './Adaptive/Adaptive';
import { socialLinks } from './dataArray';
import { DefaultFooter } from './DefaultFooter';

function Footer() {
  // Can i use useContext for socialLinks?
  return (
    <>
      <DefaultFooter socialLinks={socialLinks} />
      <Adaptive socialLinks={socialLinks} />
    </>
  );
}

export default Footer;
