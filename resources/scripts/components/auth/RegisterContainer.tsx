import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import { httpErrorToHuman } from '@/api/http';
import register from '@/api/auth/register';
import { Formik, FormikHelpers } from 'formik';
import { object, string, ref } from 'yup';

interface Values {
    email: string;
    username: string;
    name_first: string;
    name_last: string;
    password: string;
    password_confirmation: string;
}

const RegisterContainer = () => {
    const [error, setError] = useState('');

    const submit = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        setError('');

        try {
            const response = await register({
                email: values.email,
                username: values.username,
                name_first: values.name_first,
                name_last: values.name_last,
                password: values.password,
                password_confirmation: values.password_confirmation,
            });

            if (response.success) {
                window.location.href = response.redirect || '/dashboard';
            } else {
                setError(response.error || 'Failed to create account');
            }
        } catch (err) {
            setError(httpErrorToHuman(err));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                email: '',
                username: '',
                name_first: '',
                name_last: '',
                password: '',
                password_confirmation: '',
            }}
            validationSchema={object().shape({
                email: string().email('Must be a valid email').required('Email is required'),
                username: string()
                    .min(3, 'Username must be at least 3 characters')
                    .max(191)
                    .matches(/^[a-z0-9_]+$/, 'Username can only contain lowercase letters, numbers, and underscores')
                    .required('Username is required'),
                name_first: string().required('First name is required'),
                name_last: string().required('Last name is required'),
                password: string().min(8, 'Password must be at least 8 characters').required('Password is required'),
                password_confirmation: string()
                    .oneOf([ref('password')], 'Passwords must match')
                    .required('Password confirmation is required'),
            })}
        >
            {({ isSubmitting, submitForm }) => (
                <div css={tw`w-full flex`}>
                    <div css={tw`w-full md:w-1/2 mx-auto p-6 md:p-10`}>
                        <h2 css={tw`text-3xl text-center font-medium py-4`}>Create Account</h2>
                        {error && <div css={tw`bg-red-500 text-white text-sm p-3 rounded mb-4`}>{error}</div>}
                        <div css={tw`mt-6`}>
                            <Field type={'email'} label={'Email'} name={'email'} />
                        </div>
                        <div css={tw`mt-6`}>
                            <Field type={'text'} label={'Username'} name={'username'} />
                        </div>
                        <div css={tw`mt-6 grid grid-cols-2 gap-4`}>
                            <Field type={'text'} label={'First Name'} name={'name_first'} />
                            <Field type={'text'} label={'Last Name'} name={'name_last'} />
                        </div>
                        <div css={tw`mt-6`}>
                            <Field type={'password'} label={'Password'} name={'password'} />
                        </div>
                        <div css={tw`mt-6`}>
                            <Field type={'password'} label={'Confirm Password'} name={'password_confirmation'} />
                        </div>
                        <div css={tw`mt-6`}>
                            <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting} onClick={submitForm}>
                                Create Account
                            </Button>
                        </div>
                        <div css={tw`mt-6 text-center`}>
                            <Link
                                to={'/auth/login'}
                                css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                            >
                                Already have an account? Sign In
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </Formik>
    );
};

export default RegisterContainer;
