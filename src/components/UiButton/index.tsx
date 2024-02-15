/* eslint-disable react/jsx-props-no-spreading */
import { Button, ButtonProps } from '@mui/material';

import mediumContainedBtn from './MediumContainedBtn';
import smallContainerBtn from './SmallContainedBtn';
import smallOutlinedBtn from './SmallOutlinedBtn';
import socialShareBtn from './SocialShareBtn';

function UiButton(props: ButtonProps): React.ReactElement {
  return <Button {...props} />;
}

export const SmallContainedBtn: React.FC<ButtonProps> =
  smallContainerBtn(UiButton);

export const SmallOutlinedBtn: React.FC<ButtonProps> =
  smallOutlinedBtn(UiButton);

export const MediumContainedBtn: React.FC<ButtonProps> =
  mediumContainedBtn(UiButton);

export const SocialShareBtn: React.FC<ButtonProps> = socialShareBtn(UiButton);

export default UiButton;
