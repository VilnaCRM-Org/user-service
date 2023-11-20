import CustomCheckbox from '@/components/ui/CustomCheckbox/CustomCheckbox';
import React from 'react';
import { CHECKBOX_PRIVACY_POLICY_ID } from '@/features/landing/types/sign-up/types';
import { Typography } from '@mui/material';
import CustomLink from '@/components/ui/CustomLink/CustomLink';

interface ISignUpPrivacyPolicyProps {
  isCheckboxChecked: boolean;
  onPrivacyPolicyCheckboxChange: (checked: boolean) => void;
}

export default function SignUpPrivacyPolicy({
  isCheckboxChecked,
  onPrivacyPolicyCheckboxChange,
}: ISignUpPrivacyPolicyProps) {
  const handleCheckboxChange = (checked: boolean) => {
    onPrivacyPolicyCheckboxChange(checked);
  };

  const checkboxLabelJSX = (
    <Typography>
      Я прочитав та приймаю{' '}
      <CustomLink href="/" target="_blank">
        Політику Конфіденційності
      </CustomLink>{' '}
      та{' '}
      <CustomLink href="/" target="_blank">
        Політику Використання
      </CustomLink>{' '}
      сервісу VilnaCRM
    </Typography>
  );

  return (
    <CustomCheckbox
      id={CHECKBOX_PRIVACY_POLICY_ID}
      checked={isCheckboxChecked}
      onChange={handleCheckboxChange}
    >
      {checkboxLabelJSX}
    </CustomCheckbox>
  );
}
